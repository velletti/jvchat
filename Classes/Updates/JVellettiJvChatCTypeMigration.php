<?php

declare(strict_types=1);

namespace JVelletti\Jvchat\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\AbstractListTypeToCTypeUpdate;


#[UpgradeWizard('jvellettijvChatCTypeMigration')]
final class JVellettiJvChatCTypeMigration extends AbstractListTypeToCTypeUpdate
{
    public function getTitle(): string
    {
        return 'Migrate "JVchat" plugins to jvellettiChatCTypeMigration content elements.';
    }

    public function getDescription(): string
    {
        return 'The "JVE jvchat " plugins are now registered as content element. Update migrates existing records and backend user permissions.';
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
            'jvchat_pi1' => 'jvelletti_chatroom'
        ];
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
    protected function getFlexformToCTypeMapping(): array
    {
        return [
            'chat' => 'jvelletti_chatroom',
            'latestchat' => 'jvelletti_latestchat',
            'chatrooms' => 'jvelletti_chatrooms' ,
            'myrooms' => 'jvelletti_myrooms'
        ];
    }

    protected function updateContentElements(): void
    {
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $connection = $connectionPool->getConnectionForTable(self::TABLE_CONTENT);

        foreach ($this->getListTypeToCTypeMapping() as $listType => $contentType) {
            foreach ($this->getContentElementsToUpdate((string)$listType) as $record) {

                $contentTypes = $this->getFlexformToCTypeMapping() ;
                $contentType = "jvelletti_chatroom" ;
                if ( strpos($record['pi_flexform'], '<value index="vDEF">latestchat</value>') !== false ) {
                    $contentType = "latestchat";
                } elseif ( strpos($record['pi_flexform'], '<value index="vDEF">chatrooms</value>') !== false ) {
                    $contentType = "chatrooms";
                } elseif ( strpos($record['pi_flexform'], '<value index="vDEF">myrooms</value>') !== false ) {
                    $contentType = "myrooms";
                }
                $contentType = $contentTypes[ $contentType] ?? null;
                $connection->update(
                    self::TABLE_CONTENT,
                    [
                        'CType' => $contentType,
                        'list_type' => '',
                    ],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
    }

    protected function getContentElementsToUpdate(string $listType): array
    {
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->eq('list_type', $queryBuilder->createNamedParameter($listType)),
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    protected function hasContentElementsToUpdate(): bool
    {
        $listTypesToUpdate = array_keys($this->getListTypeToCTypeMapping());
        $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->count('uid')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in(
                    'list_type',
                    $queryBuilder->createNamedParameter($listTypesToUpdate, Connection::PARAM_STR_ARRAY)
                ),
            );

        return (bool)$queryBuilder->executeQuery()->fetchOne();
    }
}
