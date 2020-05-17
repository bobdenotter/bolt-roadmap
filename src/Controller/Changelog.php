<?php

declare(strict_types=1);

namespace App\Controller;

use App\Config\Configuration;
use Cocur\Slugify\Slugify;
use Github\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Tightenco\Collect\Support\Collection;

class Changelog extends AbstractController
{
    /** @var Configuration */
    private $config;
    
    /** @var string */
    private $modifiedAt = '';

    /** @var Client */
    private $client;

    /** @var Slugify */
    private $slugify;

    public function __construct(Configuration $config, Client $client)
    {
        $this->config = $config;
        $this->modifiedAt = $config->modifiedAt();
        $this->client = $client;
        $this->slugify = new Slugify();
    }

    /**
     * @Route("/changelog/{commitHash}")
     */
    public function changelog(Request $request, string $commitHash, string $stopHash = null)
    {
        if ($request->get('stop')) {
            $stopCommit = $this->client->api('repo')->commits()->show(
                $this->config->getConfig()['org'],
                $this->config->getConfig()['repository'],
                $request->get('stop')
            );
            $stopTimestamp = $stopCommit['commit']['author']['date'];
        } else {
            $stopTimestamp = false;
        }

        $commit = $this->client->api('repo')->commits()->show(
            $this->config->getConfig()['org'],
            $this->config->getConfig()['repository'],
            $commitHash
        );

        $timestamp = $commit['commit']['author']['date'];

        $prs = $this->client->api('pull_request')->all(
            $this->config->getConfig()['org'],
            $this->config->getConfig()['repository'],
            ['state' => 'closed', 'per_page' => 30]
        );

        $groups = $this->config->getConfig()['groups'];

        foreach ($prs as $pr) {
            if ($timestamp > $pr['merged_at']) {
                continue;
            }

            if ($stopTimestamp && $stopTimestamp <= $pr['merged_at']) {
                continue;
            }

            if (empty($pr['labels'])) {
                $groupedPrs['miscellaneous'][] = $pr;
                continue;
            }

            foreach($pr['labels'] as $key => $label) {
                $slug = $this->slugify->slugify($label['name']);
                $pr['labels'][$key]['slug'] = $slug;
            }

            foreach($pr['labels'] as $key => $label) {
                if (array_key_exists($slug, $groups)) {
                    $groupedPrs[$groups[$slug]][] = $pr;
                    continue(2);
                }
            }

            $groupedPrs['miscellaneous'][] = $pr;
        }

        $context = [
            'config' => $this->config,
            'commit' => $commit,
            'commithash' => $commitHash,
            'prs' => $groupedPrs,
        ];

        return $this->render('changelog.html.twig', $context);
    }
}
