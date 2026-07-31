<?php

namespace QUI\Projects;

use QUI;

class ProjectSiteDbalTest extends ProjectIntegrationTestCase
{
    public function testSiteChildCanBeCreatedAndLoadedFromTestProject(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $siteName = 'phpunit-site-' . uniqid();
        $siteTitle = 'PHPUnit Site ' . uniqid();

        $siteId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $siteName, $siteTitle): int {
            return $Root->createChild([
                'name' => $siteName,
                'title' => $siteTitle,
                'short' => 'PHPUnit short text',
                'content' => '<p>PHPUnit content</p>'
            ]);
        });

        $Site = new Site\Edit($Project, $siteId);

        $this->assertGreaterThan(1, $siteId);
        $this->assertSame($siteName, $Site->getAttribute('name'));
        $this->assertSame($siteTitle, $Site->getAttribute('title'));
        $this->assertSame('PHPUnit short text', $Site->getAttribute('short'));
        $this->assertSame('<p>PHPUnit content</p>', $Site->getAttribute('content'));
    }

    public function testProjectSitesIdsCanFilterCountAndLimitCreatedSite(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $siteName = 'phpunit-filter-site-' . uniqid();

        $siteId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $siteName): int {
            return $Root->createChild([
                'name' => $siteName,
                'title' => 'PHPUnit Filter Site'
            ]);
        });

        $count = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'name' => $siteName
            ],
            'count' => true
        ]);

        $ids = $Project->getSitesIds([
            'where' => [
                'active' => -1,
                'name' => $siteName
            ],
            'order' => 'name ASC',
            'limit' => '0,1'
        ]);

        $this->assertSame(1, (int)$count[0]['count']);
        $this->assertSame($siteId, (int)$ids[0]['id']);
    }

    public function testProjectSitesIdsSupportsLegacyArrayConditions(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $siteName = "phpunit-legacy-condition-site-" . uniqid();

        $siteId = ProjectTestHelper::runAsSystemUser(static function () use ($Root, $siteName): int {
            return $Root->createChild([
                "name" => $siteName,
                "title" => "PHPUnit Legacy Condition Site"
            ]);
        });

        $likeIds = $Project->getSitesIds([
            "where" => [
                "active" => -1,
                "name" => [
                    "type" => "LIKE",
                    "value" => $siteName
                ]
            ]
        ]);

        $inIds = $Project->getSitesIds([
            "where" => [
                "active" => -1,
                "id" => [$siteId]
            ]
        ]);

        $this->assertSame([$siteId], array_map("intval", array_column($likeIds, "id")));
        $this->assertSame([$siteId], array_map("intval", array_column($inIds, "id")));

        $notArrayValueIds = $Project->getSitesIds([
            "where" => [
                "active" => -1,
                "id" => [
                    "type" => "NOT",
                    "value" => [$siteId]
                ]
            ],
            "limit" => "0,1"
        ]);

        $this->assertIsArray($notArrayValueIds);
    }

    public function testGetSitesByInputListCombinesExplicitIdsWithWhereFilter(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();

        [$activeId, $inactiveId] = ProjectTestHelper::runAsSystemUser(static function () use ($Root): array {
            $activeId = $Root->createChild([
                "name" => "phpunit-input-list-active-" . uniqid(),
                "title" => "PHPUnit Input List Active"
            ]);
            $inactiveId = $Root->createChild([
                "name" => "phpunit-input-list-inactive-" . uniqid(),
                "title" => "PHPUnit Input List Inactive"
            ]);

            (new Site\Edit($Root->getProject(), $activeId))->activate();

            return [$activeId, $inactiveId];
        });

        $Sites = Site\Utils::getSitesByInputList($Project, [$activeId, $inactiveId], [
            "where" => [
                "active" => 1
            ],
            "limit" => 10,
            "order" => "name ASC"
        ]);

        $this->assertSame([$activeId], array_map(
            static fn(Site $Site): int => $Site->getId(),
            $Sites
        ));
    }

    public function testSiteChildrenIdsSupportLegacyArrayConditions(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $parentName = "phpunit-child-condition-parent-" . uniqid();
        $firstName = "phpunit-child-condition-a-" . uniqid();
        $secondName = "phpunit-child-condition-b-" . uniqid();
        $thirdName = "phpunit-child-condition-c-" . uniqid();
        $firstType = "phpunit/condition-a-" . uniqid();
        $secondType = "phpunit/condition-b-" . uniqid();
        $thirdType = "phpunit/condition-c-" . uniqid();

        [$parentId, $firstId, $secondId, $thirdId] = ProjectTestHelper::runAsSystemUser(
            static function () use (
                $Root,
                $parentName,
                $firstName,
                $secondName,
                $thirdName,
                $firstType,
                $secondType,
                $thirdType
            ): array {
                $parentId = $Root->createChild([
                    "name" => $parentName,
                    "title" => "PHPUnit Child Condition Parent"
                ]);
                $Parent = new Site\Edit($Root->getProject(), $parentId);
                $firstId = $Parent->createChild([
                    "name" => $firstName,
                    "title" => "PHPUnit Child Condition First"
                ]);
                $secondId = $Parent->createChild([
                    "name" => $secondName,
                    "title" => "PHPUnit Child Condition Second"
                ]);
                $thirdId = $Parent->createChild([
                    "name" => $thirdName,
                    "title" => "PHPUnit Child Condition Third"
                ]);

                foreach (
                    [
                        $firstId => $firstType,
                        $secondId => $secondType,
                        $thirdId => $thirdType
                    ] as $siteId => $type
                ) {
                    $Site = new Site\Edit($Root->getProject(), $siteId);
                    $Site->setAttribute("type", $type);
                    $Site->save();
                    $Site->activate();
                }

                return [$parentId, $firstId, $secondId, $thirdId];
            }
        );

        $Parent = new Site\Edit($Project, $parentId);

        $this->assertSame([$firstId, $secondId], $Parent->getChildrenIds([
            "where" => [
                "type" => [
                    "type" => "IN",
                    "value" => [$firstType, $secondType]
                ]
            ],
            "order" => "name ASC"
        ]));

        $this->assertSame([$thirdId], $Parent->getChildrenIds([
            "where" => [
                "type" => [$thirdType]
            ],
            "order" => "name ASC"
        ]));
    }

    public function testSiteTreeQueriesReturnChildrenParentsSiblingsAndRecursiveIds(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $parentName = 'phpunit-tree-parent-' . uniqid();
        $firstName = 'phpunit-tree-child-a-' . uniqid();
        $secondName = 'phpunit-tree-child-b-' . uniqid();
        $grandChildName = 'phpunit-tree-grandchild-' . uniqid();

        [$parentId, $firstId, $secondId, $grandChildId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $parentName, $firstName, $secondName, $grandChildName): array {
                $parentId = $Root->createChild([
                    'name' => $parentName,
                    'title' => 'PHPUnit Tree Parent'
                ]);
                $Parent = new Site\Edit($Root->getProject(), $parentId);
                $firstId = $Parent->createChild([
                    'name' => $firstName,
                    'title' => 'PHPUnit Tree First'
                ]);
                $secondId = $Parent->createChild([
                    'name' => $secondName,
                    'title' => 'PHPUnit Tree Second'
                ]);
                $First = new Site\Edit($Root->getProject(), $firstId);
                $grandChildId = $First->createChild([
                    'name' => $grandChildName,
                    'title' => 'PHPUnit Tree Grandchild'
                ]);

                (new Site\Edit($Root->getProject(), $parentId))->activate();
                (new Site\Edit($Root->getProject(), $firstId))->activate();
                (new Site\Edit($Root->getProject(), $secondId))->activate();
                (new Site\Edit($Root->getProject(), $grandChildId))->activate();

                return [$parentId, $firstId, $secondId, $grandChildId];
            }
        );

        $Parent = new Site\Edit($Project, $parentId);
        $First = new Site\Edit($Project, $firstId);
        $Second = new Site\Edit($Project, $secondId);
        $GrandChild = new Site\Edit($Project, $grandChildId);

        $this->assertSame(2, $Parent->hasChildren(true));
        $this->assertSame($firstId, $Parent->firstChild(['active' => '0&1'])->getId());
        $this->assertSame($secondId, $Parent->lastChild(['active' => '0&1'])->getId());
        $this->assertSame([$firstId, $secondId], $Parent->getChildrenIds(['active' => '0&1']));
        $this->assertSame(2, $Parent->getChildren(['active' => '0&1', 'count' => true]));
        $this->assertSame($secondId, $First->nextSiblings(1)[0]->getId());
        $this->assertSame($firstId, $Second->previousSibling()->getId());
        $this->assertSame($firstId, $Project->getParentId($grandChildId));
        $this->assertSame($firstId, $Project->getParentIdFrom($grandChildId));
        $this->assertContains($parentId, $GrandChild->getParentIdTree());
        $this->assertContains($firstId, $GrandChild->getParentIds());
        $this->assertContains($grandChildId, $Parent->getChildrenIdsRecursive(['active' => '0&1']));
    }

    public function testSiteCanBeCopiedAndLinkedAndLinkCanBeRemoved(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $sourceName = 'phpunit-copy-source-' . uniqid();
        $targetName = 'phpunit-copy-target-' . uniqid();
        $linkParentName = 'phpunit-link-parent-' . uniqid();

        [$sourceId, $targetId, $linkParentId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $sourceName, $targetName, $linkParentName): array {
                $sourceId = $Root->createChild([
                    'name' => $sourceName,
                    'title' => 'PHPUnit Copy Source',
                    'short' => 'copy source short'
                ]);
                $targetId = $Root->createChild([
                    'name' => $targetName,
                    'title' => 'PHPUnit Copy Target'
                ]);
                $linkParentId = $Root->createChild([
                    'name' => $linkParentName,
                    'title' => 'PHPUnit Link Parent'
                ]);

                return [$sourceId, $targetId, $linkParentId];
            }
        );

        $copyId = ProjectTestHelper::runAsSystemUser(static function () use ($Project, $sourceId, $targetId): int {
            $Copy = (new Site\Edit($Project, $sourceId))->copy($targetId);

            return $Copy->getId();
        });

        $Copy = new Site\Edit($Project, $copyId);
        $this->assertSame($targetId, $Copy->getParentId());
        $this->assertSame('PHPUnit Copy Source', $Copy->getAttribute('title'));
        $this->assertSame('copy source short', $Copy->getAttribute('short'));

        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $sourceId, $linkParentId): void {
            (new Site\Edit($Project, $sourceId))->linked($linkParentId);
        });

        $LinkedSource = new Site\Edit($Project, $sourceId);
        $this->assertContains($linkParentId, array_map('intval', $LinkedSource->getParentIds()));

        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $sourceId, $linkParentId): void {
            (new Site\Edit($Project, $sourceId))->deleteLinked($linkParentId);
        });

        $UnlinkedSource = new Site\Edit($Project, $sourceId);
        $this->assertNotContains($linkParentId, array_map('intval', $UnlinkedSource->getParentIds()));
    }

    public function testCopiedSiteIsAppendedToManualChildOrder(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();

        [$parentId, $firstId, $secondId, $copyId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root): array {
                $parentId = $Root->createChild([
                    'name' => 'phpunit-copy-order-parent-' . uniqid(),
                    'title' => 'PHPUnit Copy Order Parent'
                ]);
                $Parent = new Site\Edit($Root->getProject(), $parentId);
                $Parent->setAttribute('order_type', 'manuell');
                $Parent->save();

                $firstId = $Parent->createChild([
                    'name' => 'phpunit-copy-order-first-' . uniqid(),
                    'title' => 'PHPUnit Copy Order First'
                ]);
                $secondId = $Parent->createChild([
                    'name' => 'phpunit-copy-order-second-' . uniqid(),
                    'title' => 'PHPUnit Copy Order Second'
                ]);
                $copyId = (new Site\Edit($Root->getProject(), $firstId))->copy($parentId)->getId();

                return [$parentId, $firstId, $secondId, $copyId];
            }
        );

        $Parent = new Site\Edit($Project, $parentId);
        $First = new Site\Edit($Project, $firstId);
        $Second = new Site\Edit($Project, $secondId);
        $Copy = new Site\Edit($Project, $copyId);

        $this->assertSame(1, (int)$First->getAttribute('order_field'));
        $this->assertSame(2, (int)$Second->getAttribute('order_field'));
        $this->assertSame(3, (int)$Copy->getAttribute('order_field'));
        $this->assertSame(
            [$firstId, $secondId, $copyId],
            $Parent->getChildrenIds(['active' => '0&1'])
        );
    }

    public function testSiteLifecycleCanEditActivateMoveDeleteAndDestroy(): void
    {
        $Project = self::getTestProject();
        $Root = $Project->firstChild()->getEdit();
        $parentAName = 'phpunit-parent-a-' . uniqid();
        $parentBName = 'phpunit-parent-b-' . uniqid();
        $childName = 'phpunit-child-' . uniqid();

        [$parentAId, $parentBId, $childId] = ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $parentAName, $parentBName, $childName): array {
                $parentAId = $Root->createChild([
                    'name' => $parentAName,
                    'title' => 'PHPUnit Parent A'
                ]);
                $parentBId = $Root->createChild([
                    'name' => $parentBName,
                    'title' => 'PHPUnit Parent B'
                ]);
                $ParentA = new Site\Edit($Root->getProject(), $parentAId);
                $childId = $ParentA->createChild([
                    'name' => $childName,
                    'title' => 'PHPUnit Child'
                ]);

                return [$parentAId, $parentBId, $childId];
            }
        );

        $Child = new Site\Edit($Project, $childId);
        $this->assertSame($parentAId, $Child->getParentId());

        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $childId): void {
            $Child = new Site\Edit($Project, $childId);
            $Child->setAttribute('title', 'PHPUnit Child Edited');
            $Child->setAttribute('short', 'PHPUnit edited short text');
            $Child->setAttribute('content', '<p>PHPUnit edited content</p>');
            $Child->save();
            $Child->activate();
        });

        $Child = new Site\Edit($Project, $childId);
        $this->assertSame('PHPUnit Child Edited', $Child->getAttribute('title'));
        $this->assertSame('PHPUnit edited short text', $Child->getAttribute('short'));
        $this->assertSame('<p>PHPUnit edited content</p>', $Child->getAttribute('content'));
        $this->assertSame(1, (int)$Child->getAttribute('active'));

        ProjectTestHelper::runAsSystemUser(static function () use ($Project, $childId, $parentBId): void {
            $Child = new Site\Edit($Project, $childId);
            $Child->deactivate();
            $Child->move($parentBId);
        });

        $Child = new Site\Edit($Project, $childId);
        $this->assertSame(0, (int)$Child->getAttribute('active'));
        $this->assertSame($parentBId, $Child->getParentId());

        $ParentB = new Site\Edit($Project, $parentBId);
        $this->assertContains($childId, $ParentB->getChildrenIds(['active' => '0&1']));

        $deleteResult = ProjectTestHelper::runAsSystemUser(static function () use ($Project, $childId): bool {
            return (new Site\Edit($Project, $childId))->delete();
        });

        $this->assertTrue($deleteResult);

        $Child = new Site\Edit($Project, $childId);
        $this->assertSame(1, (int)$Child->getAttribute('deleted'));
        $this->assertSame(-1, (int)$Child->getAttribute('active'));

        ProjectTestHelper::runAsSystemUser(static function () use ($Child): void {
            $Child->destroy();
        });

        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $exists = $Connection->createQueryBuilder()
            ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
            ->from($Platform->quoteSingleIdentifier($Project->table()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :siteId')
            ->setParameter('siteId', $childId)
            ->executeQuery()
            ->fetchOne();

        $this->assertSame(0, (int)$exists);
    }
}
