<?php

namespace SimpleSoftwareIO\SMS;

use GuzzleHttp\Client;
use Illuminate\Support\Manager;
use SimpleSoftwareIO\SMS\Drivers\SMS77;
use SimpleSoftwareIO\SMS\Drivers\LogSMS;
use SimpleSoftwareIO\SMS\Drivers\EmailSMS;
use SimpleSoftwareIO\SMS\Drivers\MozeoSMS;
use SimpleSoftwareIO\SMS\Drivers\NexmoSMS;
use SimpleSoftwareIO\SMS\Drivers\PlivoSMS;
use SimpleSoftwareIO\SMS\Drivers\TwilioSMS;
use SimpleSoftwareIO\SMS\Drivers\ZenviaSMS;
use SimpleSoftwareIO\SMS\Drivers\CallFireSMS;
use SimpleSoftwareIO\SMS\Drivers\JustSendSMS;
use SimpleSoftwareIO\SMS\Drivers\EZTextingSMS;
use SimpleSoftwareIO\SMS\Drivers\FlowrouteSMS;
use SimpleSoftwareIO\SMS\Drivers\LabsMobileSMS;
use SimpleSoftwareIO\SMS\Drivers\IPPanelSMS;

class DriverManager extends Manager
{
    /**
     * Get the default sms driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return app()['config']['sms.driver'];
    }

    /**
     * Set the default sms driver name.
     *
     * @param string $name
     */
    public function setDefaultDriver($name)
    {
        app()['config']['sms.driver'] = $name;
    }

    /**
     * Create an instance of the Log driver.
     *
     * @return LogSMS
     */
    protected function createLogDriver()
    {
        $provider = new LogSMS(app()['log']);

        return $provider;
    }

    /**
     * Create an instance of the CallFire driver.
     *
     * @return CallFireSMS
     */
    protected function createCallfireDriver()
    {
        $config = app()['config']->get('sms.callfire', []);

        $provider = new CallFireSMS(
            new Client(),
            $config['app_login'],
            $config['app_password']
        );

        return $provider;
    }

    /**
     * Creates an instance of the EMail driver.
     *
     * @return EmailSMS
     */
    protected function createEmailDriver()
    {
        $provider = new EmailSMS(app()['mailer']);

        return $provider;
    }

    /**
     * Create an instance of the EZTexting driver.
     *
     * @return EZTextingSMS
     */
    protected function createEztextingDriver()
    {
        $config = app()['config']->get('sms.eztexting', []);

        $provider = new EZTextingSMS(new Client());

        $data = [
            'User'     => $config['username'],
            'Password' => $config['password'],
        ];

        $provider->buildBody($data);

        return $provider;
    }

    /**
     * Create an instance of the LabsMobile driver.
     *
     * @return LabsMobileSMS
     */
    protected function createLabsMobileDriver()
    {
        $config = app()['config']->get('sms.labsmobile', []);

        $provider = new LabsMobileSMS(new Client());

        $auth = [
            'username' => $config['username'],
            'password' => $config['password'],
        ];

        $provider->buildBody($auth);

        return $provider;
    }

    /**
     * Create an instance of the Mozeo driver.
     *
     * @return MozeoSMS
     */
    protected function createMozeoDriver()
    {
        $config = app()['config']->get('sms.mozeo', []);

        $provider = new MozeoSMS(new Client());

        $auth = [
            'companykey' => $config['company_key'],
            'username'   => $config['username'],
            'password'   => $config['password'],
        ];
        $provider->buildBody($auth);

        return $provider;
    }

    /**
     * Create an instance of the nexmo driver.
     *
     * @return NexmoSMS
     */
    protected function createNexmoDriver()
    {
        $config = app()['config']->get('sms.nexmo', []);

        $provider = new NexmoSMS(
            new Client(),
            $config['api_key'],
            $config['api_secret']
        );

        return $provider;
    }

    /**
     * Create an instance of the Twillo driver.
     *
     * @return TwilioSMS
     */
    protected function createTwilioDriver()
    {
        $config = app()['config']->get('sms.twilio', []);

        return new TwilioSMS(
            new \Services_Twilio($config['account_sid'], $config['auth_token']),
            $config['auth_token'],
            app()['request']->url(),
            $config['verify']
        );
    }

     /**
      * Create an instance of the Zenvia driver.
      *
      * @return ZenviaSMS
      */
     protected function createZenviaDriver()
     {
         $config = app()['config']->get('sms.zenvia', []);

         $provider = new ZenviaSMS(
             new Client(),
             $config['account_key'],
             $config['passcode'],
             $config['callbackOption']
         );

         return $provider;
     }

    /**
     * Create an instance of the Plivo driver.
     *
     * @return PlivoSMS
     */
    protected function createPlivoDriver()
    {
        $config = app()['config']->get('sms.plivo', []);

        $provider = new PlivoSMS(
            $config['auth_id'],
            $config['auth_token']
        );

        return $provider;
    }

    /**
     * Create an instance of the flowroute driver.
     *
     * @return FlowrouteSMS
     */
    protected function createFlowrouteDriver()
    {
        $config = app()['config']->get('sms.flowroute', []);

        $provider = new FlowrouteSMS(
            new Client(),
            $config['access_key'],
            $config['secret_key']
        );

        return $provider;
    }

    /**
     * Create an instance of the SMS77 driver.
     *
     * @return SMS77
     */
    protected function createSms77Driver()
    {
        $config = app()['config']->get('sms.sms77', []);

        $provider = new SMS77(
            new Client(),
            $config['user'],
            $config['api_key'],
            $config['debug']
        );

        return $provider;
    }

    /**
     * Create an instance of the justsend driver.
     *
     * @return JustSendSMS
     */
    protected function createJustSendDriver()
    {
        $config = app()['config']->get('sms.justsend', []);

        $provider = new JustSendSMS(
            $config['api_key']
        );

        return $provider;
    }
    
    /**
     * Create an instance of the ippanel driver.
     *
     * @return IPPanelSMS
     */
    protected function createIPPanelDriver()
    {
        $config = app()['config']->get('sms.ippanel', []);

        $provider = new IPPanelSMS(
            new Client(),
            $config['api_key']
        );

        return $provider;
    }
}
