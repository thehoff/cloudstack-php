<?php namespace PCextreme\Cloudstack\Util;

use InvalidArgumentException;
use PCextreme\Cloudstack\Exception\ClientException;

trait UrlHelpersTrait
{
    /**
     * Generate Console URL for specified username owning the virtual machine.
     *
     * @param  string  $username
     * @param  string  $domainId
     * @param  string  $virtualMachineId
     * @return string
     * @throws \InvalidArgumentEception
     */
    public function consoleUrl($username, $domainId, $virtualMachineId)
    {
        if (is_null($this->urlConsole)) {
            throw new InvalidArgumentException(
                'Required options not defined: urlConsole'
            );
        }

        // Prepare session.
        // Using the SSO (Single Sign On) key we can generate a sessionkey used for the console url.
        $command = 'login';
        $params = [
            'command'   => $command,
            'username'  => $username,
            'domainid'  => $domainId,
            'response'  => 'json',
        ];

        $base    = $this->urlApi;
        $method  = $this->getCommandMethod($command);
        $query   = $this->enableSso()->getCommandQuery($params);
        $url     = $this->appendQuery($base, $query);
        $request = $this->getRequest($method, $url);

        $login = $this->getResponse($request);

        // Prepare a signed request for the Console url.
        // Effectively this will be the console url, it won't be requested at the Cloudstack API.
        $params = [
            'cmd'        => 'access',
            'vm'         => $virtualMachineId,
            'userid'     => $login['loginresponse']['userid'],
            'sessionkey' => $login['loginresponse']['sessionkey'],
            'timestamp'  => round(microtime(true) * 1000),
            'apikey'     => $this->apiKey,
        ];

        $base = $this->urlConsole;
        $query  = $this->getCommandQuery($params);

        return $this->appendQuery($base, $query);
    }
}
