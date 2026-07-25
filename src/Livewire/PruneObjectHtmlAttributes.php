<?php

namespace Devdojo\Foundation\Livewire;

use Livewire\Component;
use Livewire\Mechanisms\HandleComponents\ComponentContext;

class PruneObjectHtmlAttributes
{
    /**
     * Objects can land in a component's forwarded HTML attributes when a Volt
     * fragment captures every template variable (e.g. Blaze's $__blaze runtime,
     * which Volt's argument blacklist predates). They are meaningless as HTML
     * attributes and json-encode to "{}", which the client round trip decodes
     * to [] — so Livewire's snapshot checksum no longer matches and every
     * update request fails with a CorruptComponentPayloadException.
     *
     * Registered on Livewire's event bus after the framework boots, so it runs
     * after the core component hooks have written the attributes memo and can
     * safely prune it before the snapshot checksum is generated.
     */
    public function __invoke(Component $component, ComponentContext $context): void
    {
        if (! isset($context->memo['attributes']) || ! is_array($context->memo['attributes'])) {
            return;
        }

        $attributes = array_filter(
            $context->memo['attributes'],
            fn ($value) => ! is_object($value),
        );

        if ($attributes === []) {
            unset($context->memo['attributes']);
        } else {
            $context->memo['attributes'] = $attributes;
        }
    }
}
