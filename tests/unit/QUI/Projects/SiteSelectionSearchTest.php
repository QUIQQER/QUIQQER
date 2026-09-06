<?php

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use QUI;

class SiteSelectionSearchTest extends TestCase
{
    private mixed $previousManager;
    private mixed $previousAjax;
    private array $previousProjects;
    private Project $Project;
    private \Closure $search;

    protected function setUp(): void
    {
        $this->previousManager = QUI::$ProjectManager;
        $this->previousAjax = QUI::$Ajax;
        $this->Project = $this->createMock(Project::class);
        $this->previousProjects = Manager::$projects;
        Manager::$projects['test']['de'] = $this->Project;
        QUI::$ProjectManager = new Manager();
        QUI::$Ajax = new QUI\Ajax();
        require dirname(__DIR__, 4) . '/admin/ajax/project/sites/searchForSelection.php';
        $this->search = QUI\Ajax::getRegisteredCallables()['ajax_project_sites_searchForSelection']['callable'];
    }

    protected function tearDown(): void
    {
        QUI::$ProjectManager = $this->previousManager;
        QUI::$Ajax = $this->previousAjax;
        Manager::$projects = $this->previousProjects;
    }

    private function site(int $id, bool $allowed = true, bool $deleted = false): Site
    {
        $Site = $this->createMock(Site::class);
        $Site->method('getId')->willReturn($id);
        $Site->method('hasPermission')->with('quiqqer.projects.site.view')->willReturn($allowed);
        $Site->method('getAttribute')->willReturnCallback(static fn($name) => match ($name) {
            'deleted' => $deleted,
            'name' => 'page-' . $id,
            'title' => 'Page ' . $id,
            default => null
        });
        return $Site;
    }

    public function testSearchUsesTitleAndNameAndOmitsRestrictedAndDeletedPages(): void
    {
        $this->Project->expects(self::once())->method('search')->with('page', ['name', 'title'])
            ->willReturn([$this->site(1), $this->site(2, false), $this->site(3, true, true)]);
        $result = ($this->search)('{"name":"test","lang":"de"}', ' page ');
        self::assertSame([['id' => 1, 'name' => 'page-1', 'title' => 'Page 1']], $result['items']);
        self::assertFalse($result['limited']);
    }

    public function testIdSearchIsExactAndDoesNotDuplicateTextMatches(): void
    {
        $Site = $this->site(42);
        $this->Project->method('search')->willReturn([$Site]);
        $this->Project->expects(self::once())->method('get')->with(42)->willReturn($Site);
        $result = ($this->search)('{"name":"test","lang":"de"}', '42');
        self::assertSame([42], array_column($result['items'], 'id'));
    }

    public function testSearchStaysWithinTheSelectedProjectLanguage(): void
    {
        $OtherLanguage = $this->createMock(Project::class);
        $OtherLanguage->expects(self::never())->method('search');
        Manager::$projects['test']['en'] = $OtherLanguage;
        $this->Project->expects(self::once())->method('search')->willReturn([$this->site(9)]);
        self::assertSame([9], array_column(($this->search)('{"name":"test","lang":"de"}', 'page')['items'], 'id'));
    }

    public function testIdLookupAlsoEnforcesViewPermission(): void
    {
        $this->Project->method('search')->willReturn([]);
        $this->Project->method('get')->willReturn($this->site(42, false));
        self::assertSame([], ($this->search)('{"name":"test","lang":"de"}', '42')['items']);
    }

    public function testMissingIdStillReturnsMatchingTitles(): void
    {
        $this->Project->method('search')->willReturn([$this->site(7)]);
        $this->Project->method('get')->willThrowException(new QUI\Exception('Not found', 705));
        self::assertSame([7], array_column(($this->search)('{"name":"test","lang":"de"}', '42')['items'], 'id'));
    }

    public function testEmptySearchDoesNotQueryTheProject(): void
    {
        $this->Project->expects(self::never())->method('search');
        self::assertSame(['items' => [], 'limited' => false], ($this->search)('', '  '));
    }

    public function testSearchReportsTheExistingProjectSearchLimit(): void
    {
        $this->Project->method('search')->willReturn(array_map(fn($id) => $this->site($id), range(1, 50)));
        $result = ($this->search)('{"name":"test","lang":"de"}', 'page');
        self::assertTrue($result['limited']);
        self::assertCount(50, $result['items']);
    }

    public function testEndpointRequiresBackendAuthentication(): void
    {
        $permissions = (new \ReflectionProperty(QUI\Ajax::class, 'permissions'))->getValue();
        self::assertSame('Permission::checkAdminUser', $permissions['ajax_project_sites_searchForSelection']);
    }

    public function testExcessiveSearchLengthIsRejected(): void
    {
        $this->Project->expects(self::never())->method('search');
        $this->expectExceptionCode(400);
        ($this->search)('', str_repeat('a', 201));
    }
}
