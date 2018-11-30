<?php

declare(strict_types=1);

namespace App\Command;

use App\Config\Configuration;
use App\Entity\Statistics;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Persistence\ObjectManager;
use Github\Client;
use Github\Exception\ApiLimitExceedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Github extends Command
{
    protected static $defaultName = 'app:github';

    /** @var Configuration */
    private $configuration;

    /** @var Client */
    private $client;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(Configuration $configuration, ObjectManager $objectManager, Client $client)
    {
        parent::__construct();
        $this->configuration = $configuration;
        $this->client = $client;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Update stuff from Github, using the API')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> updates stuff from Github, using the API
HELP
            )
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            // ->addOption('max-results', null, InputOption::VALUE_OPTIONAL, 'Limits the number of users listed', 50)
            // ->addOption('send-to', null, InputOption::VALUE_OPTIONAL, 'If set, the result is sent to the given email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->configuration->getConfig();

        $slugify = Slugify::create();

        $milestones = $this->client->api('issue')->milestones()->all($config['org'], $config['repository']);

        foreach ($milestones as $key => $milestone) {
            $milestones[$key]['issues'] = $this->client->api('issue')->all(
                $config['org'],
                $config['repository'],
                ['milestone' => $milestone['number'], 'state' => 'all']
            );
        }

        $this->configuration->set($milestones);
        $this->configuration->write();
        
        $output->writeLn(sprintf("Got %d milestones!", count($milestones)));
    }

    private function getReponame($url)
    {
        $url = parse_url($url);

        return ltrim($url['path'], '/');
    }

    private function getRecentlyOpenedIssues($reponame)
    {
        $query = sprintf(
            'repo:%s is:open created:>%s',
            $reponame,
            date('Y-m-d', strtotime('-30 days'))
        );
        $issues = $this->client->api('search')->issues($query);

        return $issues['total_count'];
    }

    private function getRecentlyClosedIssues($reponame)
    {
        $query = sprintf(
            'repo:%s is:closed closed:>%s',
            $reponame,
            date('Y-m-d', strtotime('-30 days'))
        );
        $issues = $this->client->api('search')->issues($query);

        return $issues['total_count'];
    }

    private function getInfo($reponame)
    {
        $reponame = explode('/', $reponame);

        return $this->client->api('repo')->show($reponame[0], $reponame[1]);
    }

    private function getTopics($reponame)
    {
        $reponame = explode('/', $reponame);

        $topics = $this->client->api('repo')->topics($reponame[0], $reponame[1]);

        return $topics['names'];
    }

    private function getCommits($reponame)
    {
        $reponame = explode('/', $reponame);

        $commits = collect($this->client->api('repo')->activity($reponame[0], $reponame[1]));

        $res = [
            'year' => $commits->sum('total'),
            'month' => $commits->slice(-4)->sum('total'),
        ];

        return $res;
    }
}
