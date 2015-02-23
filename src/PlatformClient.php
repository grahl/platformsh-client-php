<?php

namespace Platformsh\Client;

use Platformsh\Client\Connection\Connector;
use Platformsh\Client\Connection\ConnectorInterface;
use Platformsh\Client\Model\Project;

class PlatformClient
{

    /** @var ConnectorInterface */
    protected $connector;

    /** @var array */
    protected $accountInfo;

    /**
     * @param ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector = null)
    {
        $this->connector = $connector ?: new Connector();
    }

    /**
     * @return ConnectorInterface
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * Get account information for the logged in user.
     *
     * @param bool $reset
     *
     * @return array
     */
    public function getAccountInfo($reset = false)
    {
        if (!isset($this->accountInfo) || $reset) {
            $client = $this->getConnector()->getClient();
            $this->accountInfo = (array) $client->get('me')->json();
        }

        return $this->accountInfo;
    }

    /**
     * Get the logged in user's projects.
     *
     * @param bool $reset
     *
     * @return Project[]
     */
    public function getProjects($reset = false)
    {
        $connector = $this->getConnector();
        $data = $this->getAccountInfo($reset);
        $projects = [];
        foreach ($data['projects'] as $project) {
            // Each project has its own endpoint on a Platform.sh cluster.
            $client = $connector->getClient($project['endpoint']);
            $projects[$project['id']] = new Project($project, $client);
        }

        return $projects;
    }

    /**
     * Get a single project by its ID.
     *
     * @param string $id
     * @param string $endpoint
     *
     * @return Project|false
     */
    public function getProject($id, $endpoint = null)
    {
        if ($endpoint !== null) {
            $project = $this->getProjectDirect($id, $endpoint);
        } else {
            $projects = $this->getProjects();
            if (!isset($projects[$id])) {
                return false;
            }
            $project = $projects[$id];
        }

        return $project;
    }

    /**
     * Get a single project with a known endpoint.
     *
     * @param string $id
     * @param string $endpoint
     *
     * @return Project|false
     */
    protected function getProjectDirect($id, $endpoint)
    {
        $client = $this->getConnector()->getClient($endpoint);

        return Project::get($id, $endpoint, $client);
    }
}
