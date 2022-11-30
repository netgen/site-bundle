<?php

declare(strict_types=1);

namespace Netgen\Bundle\SiteBundle\DataCollector;

use SebastianFeldmann\Git\Repository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Throwable;

class GitDataCollector extends DataCollector
{
    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $repository = new Repository();

        $branch = $repository->getInfoOperator()->getCurrentBranch();
        $lastCommitHash = $repository->getInfoOperator()->getCurrentCommitHash();

        $this->data = [
            'git_branch' => $branch,
            'last_commit_hash' => $lastCommitHash,
        ];
    }

    public function getGitBranch(): string
    {
        return $this->data['git_branch'];
    }

    public function getLastCommitHash(): string
    {
        return $this->data['last_commit_hash'];
    }

    public function reset(): void
    {
        $this->data = [
            'git_branch' => '',
            'last_commit_hash' => '',
        ];
    }

    public function getName(): string
    {
        return 'ngsite.data_collector.git';
    }
}
