<?php

/**
 * Craft Shopify config.php
 *
 * This file exists only as a template for the Craft Shopify settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'craft-shopify.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [

    // The API key for communcation with Shopify
    "apiKey" => '',

    // The API password for communcation with Shopify
    "apiPassword" => '',

    // Hostname of your Shopify instance
    "hostname" => '',

    // Secret used to sign webhook requests .
    "webhookSecret" => '',

    // Path to template file used to render product HTML sent to Shopify
    "sectionOptions" => '',

    // Path to template file used to render product previews
    "previewPath" => '',
];
