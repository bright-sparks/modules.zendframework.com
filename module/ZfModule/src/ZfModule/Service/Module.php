<?php

namespace ZfModule\Service;

use EdpGithub\Client;
use stdClass;
use ZfcBase\EventManager\EventProvider;
use ZfModule\Mapper;

class Module extends EventProvider
{
    /**
     * @var Mapper\Module
     */
    private $moduleMapper;

    /**
     * @var Client
     */
    private $githubClient;

    /**
     * @param Mapper\Module $moduleMapper
     * @param Client $githubClient
     */
    public function __construct(Mapper\Module $moduleMapper, Client $githubClient)
    {
        $this->moduleMapper = $moduleMapper;
        $this->githubClient = $githubClient;
    }

    /**
     * createFromForm
     *
     * @param array $data
     * @return \ZfcUser\Entity\UserInterface
     * @throws Exception\InvalidArgumentException
     */
    public function register($data)
    {
        $url = $data->html_url;
        $module = $this->moduleMapper->findByUrl($url);
        $update = true;
        if (!$module) {
            $module  = new \ZfModule\Entity\Module();
            $update = false;
        }

        $module->setName($data->name);
        $module->setDescription($data->description);
        $module->setUrl($data->html_url);
        $owner = $data->owner;
        $module->setOwner($owner->login);
        $module->setPhotoUrl($owner->avatar_url);

        if ($update) {
            $this->moduleMapper->update($module);
        } else {
            $this->moduleMapper->insert($module);
        }

        return $module;
    }

    /**
     * Check if Repo is a ZF Module
     *
     * @param stdClass $repository
     * @return bool
     */
    public function isModule(stdClass $repository)
    {
        $query = 'repo:' . $repository->owner->login . '/' . $repository->name . ' filename:Module.php "class Module"';
        $response = $this->githubClient->getHttpClient()->request('search/code?q=' . $query);
        $result = json_decode($response->getbody(), true);

        if (isset($result['total_count']) && $result['total_count'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param array $options array of options
     * @return array Array of modules
     */
    public function listModule($options = null)
    {
        $user = isset($options['user']) ? $options['user'] : false;

        if (!$user) {
            $limit = isset($options['limit']) ? $options['limit'] : null;

            return $this->listAllModules($limit);
        }

        return $this->listUserModules();
    }

    /**
     * @param $limit
     * @return \Zend\Db\ResultSet\HydratingResultSet
     */
    public function listAllModules($limit)
    {
        return $this->moduleMapper->findAll(
            $limit,
            'created_at',
            'DESC'
        );
    }

    /**
     * @return array
     */
    public function listUserModules()
    {
        $repositories = $this->githubClient->api('current_user')->repos([
            'type' => 'all',
            'per_page' => 100,
        ]);

        $modules = [];

        array_walk($repositories, function ($repository) use (&$modules) {
            if (true === $repository->fork) {
                return;
            }

            if (false === $repository->permissions->push) {
                return;
            }

            $module = $this->moduleMapper->findByName($repository->name);
            if (null === $module) {
                return;
            }

            array_push($modules, $module);
        });

        return $modules;
    }
}
