<?php

namespace Tenseg\CascadingEntryProtection;

use Tenseg\CascadingEntryProtection\Protectors\CascadingEntryProtector;
use Tenseg\CascadingEntryProtection\Http\Controllers\TensegController;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Auth\Protect\ProtectorManager;
use Statamic\Facades\Collection;
use Statamic\Facades\Addon;
use Statamic\Support\Str;
use Statamic\Auth\Protect\Protectors\Password\Controller as PasswordProtectController;
use Illuminate\Support\Facades\Log;

class ServiceProvider extends AddonServiceProvider
{
    protected $vite = [
        'input' => [
            'resources/js/addon.js',
        ],
        'publicDirectory' => 'resources/dist',
    ];

    public function bootAddon()
    {
        // don't include our modifications unless there are protected collections
        // this makes it easier to "turn off" the addon by choosing to protect nothing
        if ( $protected_collections = Addon::get('tenseg/cascading-entry-protection')->settings()->get('collections') ) {

            app(ProtectorManager::class)->extend('cascading_entry_protector', function ($app) {
                return new CascadingEntryProtector;
            });

            // add our custom protect scheme configuration to the Statamic config
            $this->mergeConfigFrom(__DIR__.'/Config/protect.php', 'statamic.protect.schemes');

            $this->app->bind(PasswordProtectController::Class, TensegController::Class);

            // It seems that computed values cannot reliably refer to other computed values,
            // so we have to do the cascading in both values. We cascade the tickets into
            // an inherited value so that we can see parent ticket choices from kids.
            // We cascade the protect value only when it has not been explicity set already.
            Collection::computed( $protected_collections, [

                /**
                 * Returns the inherited tickets.
                 *
                 * @return mixed the Dictionary of tickets or null
                 */
                'inherited_cascading_protection_tickets' => function ($entry, $value) {
                    if ( $value ) {
                        return $value;
                    }
                    $parent = $entry;
                    while ( $parent ) {
                        if ( $tickets = $parent->cascading_protection_tickets ) {
                            return $parent->cascading_protection_tickets;
                        }
                        $parent = $parent->parent;
                    }
                    return null;
                },

                /**
                 * Returns a string that describes the inherited tickets
                 * and where they were found.
                 *
                 * @return string
                 */
                'cep_ancestor_tickets' => function ($entry, $value) {
                    $protection = $entry->protect;
                    if ( $protection && $protection !== 'cascading_entry_protector'  ) {
                        return "Protected by " . ($protection ?? "something else") . ".";
                    }
                    if ( $entry->cascading_protection_tickets ) {
                        return "";
                    }
                    $parent = $entry;
                    while ( $parent ) {
                        if ( $tickets = $parent->cascading_protection_tickets ) {
                            return $parent->title . " provides " . Str::makeSentenceList($tickets) .  ".";
                        }
                        $parent = $parent->parent;
                    }
                    return "Open access.";
                },

                /**
                 * Returns the 'cascading_entry_protector' scheme when it
                 * finds 'cascading_protection_tickets' in the entry or ancestors.
                 *
                 * Note that this will always return the scheme actually defined
                 * in this field, if one is directly defined on this entry.
                 *
                 * @return string|null scheme
                 */
                'protect' => function ($entry, $value) {
                    if ( $value ) {
                        return $value;
                    }
                    $parent = $entry;
                    while ( $parent ) {
                        if ( $parent->cascading_protection_tickets ) {
                            return 'cascading_entry_protector';
                        }
                        $parent = $parent->parent;
                    }
                    return null;
                },

            ]);

        }
    }
}
