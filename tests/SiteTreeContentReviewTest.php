<?php

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class SiteTreeContentReviewTest extends ContentReviewBaseTest
{
    /**
     * @var string
     */
    public static $fixture_file = "contentreview/tests/ContentReviewTest.yml";

    /**
     * @var array
     */
    protected $requiredExtensions = array(
        "SiteTree"              => array("SiteTreeContentReview"),
        "Group"                 => array("ContentReviewOwner"),
        "Member"                => array("ContentReviewOwner"),
        "CMSPageEditController" => array("ContentReviewCMSExtension"),
        "SiteConfig"            => array("ContentReviewDefaultSettings"),
    );

    public function testOwnerNames()
    {
        /** @var Member $editor */
        $editor = $this->objFromFixture("Member", "editor");

        $this->logInAs($editor);

        /** @var Page|SiteTreeContentReview $page */
        $page = new Page();
        $page->ReviewPeriodDays = 10;
        $page->ContentReviewType = "Custom";

        $page->ContentReviewUsers()->push($editor);
        $page->write();

        $this->assertTrue($page->canPublish());
        $this->assertTrue($page->doPublish());
        $this->assertEquals($page->OwnerNames, "Test Editor", "Test Editor should be the owner");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "about");

        $page->OwnerUsers()->removeAll();
        $page->write();

        $this->assertTrue($page->canPublish());
        $this->assertTrue($page->doPublish());
        $this->assertEquals("", $page->OwnerNames);
    }

    public function testPermissionsExists()
    {
        $perms = singleton("SiteTreeContentReview")->providePermissions();

        $this->assertTrue(isset($perms["EDIT_CONTENT_REVIEW_FIELDS"]));
    }

    public function testUserWithPermissionCanEdit()
    {
        /** @var Member $editor */
        $editor = $this->objFromFixture("Member", "editor");

        $this->logInAs($editor);

        /** @var Page|SiteTreeContentReview $page */
        $page = new Page();

        $fields = $page->getSettingsFields();

        $this->assertNotNull($fields->dataFieldByName("NextReviewDate"));
    }

    public function testUserWithoutPermissionCannotEdit()
    {
        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        $this->logInAs($author);

        /** @var Page|SiteTreeContentReview $page */
        $page = new Page();

        $fields = $page->getSettingsFields();

        $this->assertNull($fields->dataFieldByName("NextReviewDate"));
    }

    public function testAutomaticallyToNotSetReviewDate()
    {
        /** @var Member $editor */
        $editor = $this->objFromFixture("Member", "editor");

        $this->logInAs($editor);

        /** @var Page|SiteTreeContentReview $page */
        $page = new Page();

        $page->ReviewPeriodDays = 10;
        $page->write();

        $this->assertTrue($page->doPublish());
        $this->assertEquals(null, $page->NextReviewDate);
    }

    public function testAddReviewNote()
    {
        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "home");

        $page->addReviewNote($author, "This is a message");

        /** @var Page|SiteTreeContentReview $page */
        $homepage = $this->objFromFixture("Page", "home");

        $this->assertEquals(1, $homepage->ReviewLogs()->count());
        $this->assertEquals("This is a message", $homepage->ReviewLogs()->first()->Note);
    }

    public function testGetContentReviewOwners()
    {
        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "group-owned");

        $owners = $page->ContentReviewOwners();

        $this->assertEquals(1, $owners->count());
        $this->assertEquals("author@example.com", $owners->first()->Email);
    }

    public function testCanNotBeReviewBecauseNoReviewDate()
    {
        SS_Datetime::set_mock_now("2010-01-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "no-review");

        $this->assertFalse($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanNotBeReviewedBecauseInFuture()
    {
        SS_Datetime::set_mock_now("2010-01-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "staff");

        $this->assertFalse($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanNotBeReviewedByUser()
    {
        SS_Datetime::set_mock_now("2010-03-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "home");

        $this->assertFalse($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanBeReviewedByUser()
    {
        SS_Datetime::set_mock_now("2010-03-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "staff");

        $this->assertTrue($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanNotBeReviewedByGroup()
    {
        SS_Datetime::set_mock_now("2010-03-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "editor");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "contact");

        $this->assertFalse($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanBeReviewedByGroup()
    {
        SS_Datetime::set_mock_now("2010-03-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "contact");

        $this->assertTrue($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testCanBeReviewedFromInheritedSetting()
    {
        SS_Datetime::set_mock_now("2013-03-01 12:00:00");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        /** @var Page|SiteTreeContentReview $parentPage */
        $parentPage = $this->objFromFixture("Page", "contact");

        $parentPage->NextReviewDate = "2013-01-01";
        $parentPage->write();

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "contact-child");

        $this->assertTrue($page->canBeReviewedBy($author));

        SS_Datetime::clear_mock_now();
    }

    public function testReviewActionVisibleForAuthor()
    {
        SS_Datetime::set_mock_now("2020-03-01 12:00:00");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "contact");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        $this->logInAs($author);

        $fields = $page->getCMSActions();

        $this->assertNotNull($fields->fieldByName("action_reviewed"));

        SS_Datetime::clear_mock_now();
    }

    public function testReviewActionNotVisibleForEditor()
    {
        SS_Datetime::set_mock_now("2020-03-01 12:00:00");

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "contact");

        /** @var Member $author */
        $author = $this->objFromFixture("Member", "editor");

        $this->logInAs($author);

        $fields = $page->getCMSActions();

        $this->assertNull($fields->fieldByName("action_reviewed"));

        SS_Datetime::clear_mock_now();
    }
}
