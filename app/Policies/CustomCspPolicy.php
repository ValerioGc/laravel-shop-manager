<?php

namespace App\Policies;

use Spatie\Csp\Policies\Policy;
use Spatie\Csp\Directive;

class CustomCspPolicy extends Policy
{
    /**
     * Configure the CSP policy. 
     * Allow 'self' and the domains specified in the CORS_ALLOWED_ORIGINS environment variable.
     * @return void
     */
    public function configure()
    {
        $connectDomains = explode(',', env('CORS_ALLOWED_ORIGINS', "'self'"));
        $reportUrl = env('APP_URL') . '/api/csp-violation';

        $this
            ->addDirective(Directive::DEFAULT, 'self')
            ->addDirective(Directive::CONNECT, array_merge(["'self'"], $connectDomains))
            ->reportTo($reportUrl);
    }
}
