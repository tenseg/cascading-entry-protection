<?php

namespace Tenseg\CascadingEntryProtection\Protectors;

use Statamic\Auth\Protect\Protectors\Password\PasswordProtector;
use Facades\Statamic\Auth\Protect\Protectors\Password\Token;
use Statamic\Facades\Addon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

Log::debug('CEP loading');

class CascadingEntryProtector extends PasswordProtector
{
    public function not_protect() {
        $heads = (bool) random_int(0, 1);
        abort_if($heads, 403);
    }

    public function protect()
    {
        Log::debug('CEP protecting with ' . $this->scheme);

        if (request()->isLivePreview()) {
            return;
        }

        if ($this->isPasswordFormUrl()) {
            return;
        }

        if (!$this->hasEnteredValidPassword()) {
            Log::debug('CEP no valid password');
            $this->redirectToPasswordForm();
        }

        Log::debug('CEP happy ending');

        // abort(403, 'Forbidden. But we have tickets.');

        // if ($this->data->alt_protect_custom_password == null && $siteDefaultPassword == null) {
        //     abort(403);
        // }

    }

    /**
     * Checks both cookie and session to see if a working watchword is present.
     *
     * @return bool valid watchword found
     */
    public function hasEnteredValidPassword()
    {
        $sessionPassword = session("statamic:protect:password.passwords.{$this->scheme}.{$this->url}");
        $saved_watchwords = json_decode(request()->cookie('cep_ww') ?? '[]');
        if (! in_array($sessionPassword, $saved_watchwords)) {
            $saved_watchwords[] = $sessionPassword;
        }

        Log::debug("CEP checking: " . join(', ', $saved_watchwords));

        if (count($saved_watchwords) > 0) {
            if ($tickets = Addon::get('tenseg/cascading-entry-protection')->settings()->get('tickets')) {
                $page_tickets = $this->data->inherited_cascading_protection_tickets;
                $watchwords = [];
                foreach( $tickets as $ticket ) {
                    if (in_array(Arr::get($ticket, 'name', ''), $page_tickets)) {
                        $watchwords = array_unique(array_merge($watchwords, Arr::get($ticket, 'watchwords', [])));
                    }
                }
                foreach ($watchwords as $ww) {
                    foreach ($saved_watchwords as $saved_ww) {
                        if ((new CustomGuard($ww))->check($saved_ww)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    protected function isPasswordFormUrl()
    {
        return $this->url === $this->getPasswordFormUrl();
    }

    protected function getPasswordFormUrl()
    {
        return url($this->config['form_url'] ?? route('statamic.protect.password.show'));
    }

    protected function redirectToPasswordForm()
    {
        $url = $this->getPasswordFormUrl() . '?token=' . $this->generateToken();

        abort(redirect($url));
    }

    protected function generateToken()
    {
        $token = Token::generate();

        session()->put("statamic:protect:password.tokens.$token", [
            'scheme' => $this->scheme,
            'url' => $this->url,
            'reference' => $this->data->reference()
        ]);

        return $token;
    }

}
