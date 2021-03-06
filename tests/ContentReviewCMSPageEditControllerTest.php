<?php

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class ContentReviewCMSPageEditControllerTest extends ContentReviewBaseTest
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

    public function testReviewedThrowsExceptionWithNoRecordID()
    {
        $this->setExpectedException("SS_HTTPResponse_Exception");

        /** @var CMSPageEditController|ContentReviewCMSExtension $controller */
        $controller = new CMSPageEditController();

        $dummyForm = new CMSForm($controller, "EditForm", new FieldList(), new FieldList());

        $controller->reviewed(array(
            "ID"      => null,
            "Message" => null,
        ), $dummyForm);
    }

    public function testReviewedThrowsExceptionWithWrongRecordID()
    {
        $this->setExpectedException("SS_HTTPResponse_Exception");

        /** @var CMSPageEditController|ContentReviewCMSExtension $controller */
        $controller = new CMSPageEditController();

        $dummyForm = new CMSForm($controller, "EditForm", new FieldList(), new FieldList());

        $controller->reviewed(array(
            "ID"      => "FAIL",
            "Message" => null,
        ), $dummyForm);
    }

    public function testReviewedWithAuthor()
    {
        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        $this->loginAs($author);

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "home");

        $data = array(
            "action_reviewed" => 1,
            "ID"              => $page->ID,
        );

        $response = $this->post("admin/pages/edit/EditForm", $data);

        $this->assertEquals("OK", $response->getStatusDescription());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSaveReview()
    {
        /** @var Member $author */
        $author = $this->objFromFixture("Member", "author");

        $this->loginAs($author);

        /** @var Page|SiteTreeContentReview $page */
        $page = $this->objFromFixture("Page", "home");

        $data = array(
            "action_save_review" => 1,
            "ID"                 => $page->ID,
            "ReviewNotes"        => "This is the best page ever",
        );

        $response = $this->post("admin/pages/edit/EditForm", $data);

        $this->assertEquals("OK", $response->getStatusDescription());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $page->ReviewLogs()->count());

        $reviewLog = $page->ReviewLogs()->first();

        $this->assertEquals($data["ReviewNotes"], $reviewLog->Note);
    }
}
