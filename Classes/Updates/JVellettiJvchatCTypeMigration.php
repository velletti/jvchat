<?php

declare(strict_types=1);

namespace JVelletti\Jvchat\Updates;

use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;

#[UpgradeWizard('jvellettiJvchatCTypeMigration')]
final class JVellettiJvchatCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate "JVelletti Jvchat" plugins to content elements.';
    }

    public function getDescription(): string
    {
        return 'The "JVelletti Jvchat" plugins are now registered as content element. Update migrates existing records and backend user permissions.';
    }

    /**
     * This must return an array containing the "list_type" to "CType" mapping
     *
     *  Example:
     *
     *  [
     *      'pi_plugin1' => 'pi_plugin1',
     *      'pi_plugin2' => 'new_content_element',
     *  ]
     *
     * @return array<string, string>
     */
    protected function getListTypeToCTypeMapping(): array
    {
        return [
            'jvchat_pi1' => 'jvchat_pi1',
        ];
    }
}
