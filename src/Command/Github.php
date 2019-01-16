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
use Symfony\Component\Console\Helper\ProgressBar;
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

        $milestones = $this->client->api('issue')->milestones()->all(
            $config['org'],
            $config['repository'],
            ['state' => 'all']
        );

        $progressBar = new ProgressBar($output, count($milestones));

        foreach ($milestones as $key => $milestone) {
            $tempissues = collect($this->client->api('issue')->all(
                $config['org'],
                $config['repository'],
                ['milestone' => $milestone['number'], 'state' => 'all']
            ));

            $output->writeln(
                'Milestone: ' . $milestone['title'] . "\n",
                OutputInterface::VERBOSITY_VERBOSE
            );

            $milestones[$key]['issues'] = [];

            foreach($tempissues as $issue) {

                $issue['assignee'] = $issue['assignee'] ? $issue['assignee']['login'] : null;
                $issue['user'] = $issue['user'] ? $issue['user']['login'] : null;

                $milestones[$key]['issues'][$issue['number']] = collect($issue)
                    ->only(['html_url', 'number', 'title', 'user', 'assignee', 'labels', 'state', 'created_at', 'updated_at', 'closed_at', 'body'])
                    ->all();

            }
            $progressBar->advance();

        }

        $this->configuration->set($milestones);
        $this->configuration->write();

        $progressBar->finish();

        $output->writeLn(sprintf("\n\nGot %d milestones!", count($milestones)));
    }
}
