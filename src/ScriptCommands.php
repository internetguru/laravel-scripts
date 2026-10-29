<?php

namespace InternetGuru\LaravelScripts;

use Composer\Command\ScriptAliasCommand;
use Composer\Plugin\Capability\CommandProvider;

/** Registers each script as a command, so it gets a description and runs as `composer <script>` too. */
class ScriptCommands implements CommandProvider
{
    public function getCommands(): array
    {
        $commands = [];
        foreach (ComposerPlugin::DESCRIPTIONS as $script => $description) {
            $commands[] = new ScriptAliasCommand($script, $description);
        }

        return $commands;
    }
}
