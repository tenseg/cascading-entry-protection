<?php

namespace Tenseg\CascadingEntryProtection\Http\Controllers;

use Tenseg\CascadingEntryProtection\Protectors\CustomGuard;
use Statamic\Auth\Protect\Protectors\Password\Controller as PasswordProtectController;
use Statamic\Auth\Protect\Protectors\Password\PasswordProtector;
use Statamic\Auth\Protect\ProtectorManager;
use Statamic\Facades\Data;
use Statamic\Facades\Entry;
use Statamic\Facades\Addon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TensegController extends PasswordProtectController {

    protected $tokenData;
    protected $password;

    /**
     * Responsible for checking the password we've been given
     * and, if it is valid, making sure it is properly stored.
     *
     * @return mixed ends in a redirect or an error
     */
    public function store()
    {
        $this->password = request('password');
        $this->tokenData = session('statamic:protect:password.tokens.'.request('token'));

        Log::debug("CEP store", ['password' => $this->password, 'token data' => $this->tokenData]);

        if (! $this->tokenData) {
            return back()->withErrors(['token' => __('statamic::messages.password_protect_token_invalid')], 'passwordProtect');
        }

        if($this->tokenData['scheme'] == 'cascading_entry_protector') {
            // Cascading Entry Protector
            Log::debug("CEP Custom Password");

            $watchwords = [];
            if ($tickets = Addon::get('tenseg/cascading-entry-protection')->settings()->get('tickets')) {
                $page_tickets = $this->getCascadingTickets();
                foreach( $tickets as $ticket ) {
                    if (in_array(Arr::get($ticket, 'name', ''), $page_tickets)) {
                        $watchwords = array_unique(array_merge($watchwords, Arr::get($ticket, 'watchwords', [])));
                    }
                }
            }

            Log::debug("CEP tickets", ['page_tickets' => $page_tickets, 'tickets' => $tickets, 'watchwords' => $watchwords] );

            $valid = false;
            foreach ($watchwords as $password) {
                if ((new CustomGuard($password))->check($this->password)) {
                    $valid = true;
                    break;
                }
            }

            if (! $valid) {
                return back()->withErrors(['password' => __('statamic::messages.password_protect_incorrect_password')], 'passwordProtect');
            }

            return $this
                ->storeCustomPassword()
                ->expireToken()
                ->redirect();
        } else {
            // General Statamic Password
            // this code must mimic what you find in the `storePassword` method of
            // statamic/cms/Auth/Protect/Protectors/Password/Controller.php
            Log::debug("CEP Statamic Password");

            if (is_null($this->password) || ! $this->driver()->isValidPassword($this->password)) {
                Log::debug("CEP null password");
                return back()->withErrors(['password' => __('statamic::messages.password_protect_incorrect_password')], 'passwordProtect');
            }

            Log::debug("CEP store returning");
            return $this
                ->storePassword() // hits the parent class
                ->expireToken()
                ->redirect();
        }
    }

    /**
     * Duplicates the parent's driver method.
     * See: statamic/cms/Auth/Protect/Protectors/Password/Controller.php
     * Must be duplicated because this is a private method.
     *
     * @return PasswordProtector
     */
    private function driver(): PasswordProtector
    {
        return app(ProtectorManager::class)
            ->driver($this->getScheme())
            ->setData(Data::find($this->getReference()));
    }

    /**
     * We store the current password in both the session and a cookie.
     *
     * The session storage is required because the cookie takes a while to
     * write out to the browser and be read back in. So the session cookie
     * tides us over to the page reload.
     *
     * @return TensegController
     */
    protected function storeCustomPassword()
    {
        session()->put(
            "statamic:protect:password.passwords.{$this->getScheme()}.{$this->getUrl()}",
            $this->password
        );

        $lifetime = Addon::get('tenseg/cascading-entry-protection')->settings()->get('lifetime') ?? 30;
        $watchwords = json_decode(request()->cookie('cep_ww') ?? '[]');
        if (! in_array($this->password, $watchwords)) {
            $watchwords[] = $this->password;
        }
        cookie()->queue('cep_ww', json_encode($watchwords), 60 * 24 * $lifetime);

        return $this;
    }

    /**
     * This returns an array of cascading protetion ticket names.
     *
     * Note that the actual "cascade" is taken care of in a computed value.
     *
     * @return array ticket names
     */
    protected function getCascadingTickets()
    {
        $url = $this->getUrl();
        $entry = Entry::findByUri((parse_url($url)['path'] ?? '/'),);
        $tickets = $entry->inherited_cascading_protection_tickets ?? [];
        return $tickets;
    }

}
