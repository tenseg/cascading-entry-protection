<?php

namespace Tenseg\CascadingEntryProtection\Dictionaries;

use Statamic\Dictionaries\BasicDictionary;
use Statamic\Facades\Addon;

class ProtectionTickets extends BasicDictionary
{
    protected function getItems(): array
    {
        $result = [];
        $tickets = Addon::get('tenseg/cascading-entry-protection')->settings()->get('tickets');
        if ($tickets) {
            foreach ($tickets as $ticket) {
                $result[] = ['label' => $ticket['name'], 'value' => $ticket['name']];
            }
        }
        return $result;
    }
}
