<?php

class VirtualPageTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/VirtualPageTest.yml';
	
	/**
	 * Test that, after you update the source page of a virtual page, all the virtual pages
	 * are updated
	 */
	function testEditingSourcePageUpdatesVirtualPages() {
		$master = $this->objFromFixture('Page', 'master');
		$master->Title = "New title";
		$master->MenuTitle = "New menutitle";
		$master->Content = "<p>New content</p>";
		$master->write();
		
		$vp1 = $this->objFromFixture('VirtualPage', 'vp1');
		$vp2 = $this->objFromFixture('VirtualPage', 'vp2');
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("New menutitle", $vp1->MenuTitle);
		$this->assertEquals("New menutitle", $vp2->MenuTitle);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
	}

	/**
	 * Test that, after you publish the source page of a virtual page, all the already published
	 * virtual pages are published
	 */
	function testPublishingSourcePagePublishesAlreadyPublishedVirtualPages() {
		$this->logInWithPermssion('ADMIN');

		$master = $this->objFromFixture('Page', 'master');
		$master->doPublish();

		$master->Title = "New title";
		$master->MenuTitle = "New menutitle";
		$master->Content = "<p>New content</p>";
		$master->write();

		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		$this->assertTrue($vp1->doPublish());
		$this->assertTrue($vp2->doPublish());

		$master->doPublish();

		Versioned::reading_stage("Live");
		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		
		$this->assertNotNull($vp1);
		$this->assertNotNull($vp2);
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("New menutitle", $vp1->MenuTitle);
		$this->assertEquals("New menutitle", $vp2->MenuTitle);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
		Versioned::reading_stage("Stage");
	}
	
	/**
	 * Test that virtual pages get the content from the master page when they are created.
	 */
	function testNewVirtualPagesGrabTheContentFromTheirMaster() {
		$vp = new VirtualPage();
		$vp->write();
		
		$vp->CopyContentFromID = $this->idFromFixture('Page', 'master');
		$vp->write();
		
		$this->assertEquals("My Page", $vp->Title);
		$this->assertEquals("My Page Nav", $vp->MenuTitle);

		$vp->CopyContentFromID = $this->idFromFixture('Page', 'master2');
		$vp->write();

		$this->assertEquals("My Other Page", $vp->Title);
		$this->assertEquals("My Other Page Nav", $vp->MenuTitle);
	}
	
	/**
	 * Virtual pages are always supposed to chose the same content as the published source page.
	 * This means that when you publish them, they should show the published content of the source
	 * page, not the draft content at the time when you clicked 'publish' in the CMS.
	 */
	function testPublishingAVirtualPageCopiedPublishedContentNotDraftContent() {
		$p = new Page();
		$p->Content = "published content";
		$p->write();
		$p->doPublish();
		
		// Don't publish this change - published page will still say 'published content'
		$p->Content = "draft content";
		$p->write();
		
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		
		$vp->doPublish();
		
		// The draft content of the virtual page should say 'draft content'
		$this->assertEquals('draft content',
			DB::query('SELECT "Content" from "SiteTree" WHERE ID = ' . $vp->ID)->value());

		// The published content of the virtual page should say 'published content'
		$this->assertEquals('published content',
			DB::query('SELECT "Content" from "SiteTree_Live" WHERE ID = ' . $vp->ID)->value());
	}

	function testCantPublishVirtualPagesBeforeTheirSource() {
		// An unpublished source page
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		
		// With no source page, we can't publish
		$vp = new VirtualPage();
		$vp->write();
		$this->assertFalse($vp->canPublish());

		// When the source page isn't published, we can't publish
		$vp->CopyContentFromID = $p->ID;
		$vp->write();
		$this->assertFalse($vp->canPublish());
		
		// Once the source page gets published, then we can publish
		$p->doPublish();
		$this->assertTrue($vp->canPublish());
	}

	function testCanDeleteOrphanedVirtualPagesFromLive() {
		// An unpublished source page
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		$p->doPublish();
		
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// Delete the source page
		$this->assertTrue($vp->canPublish());
		$this->assertTrue($p->doDeleteFromLive());
		
		// Confirm that we can unpublish, but not publish
		$this->assertTrue($vp->canDeleteFromLive());
		$this->assertFalse($vp->canPublish());
		
		// Confirm that the action really works
		$this->assertTrue($vp->doDeleteFromLive());
		$this->assertNull(DB::query("SELECT \"ID\" FROM \"SiteTree_Live\" WHERE \"ID\" = $vp->ID")->value());
	}
	
	function testVirtualPagesArentInappropriatelyPublished() {
		// Fixture
		$p = new Page();
		$p->Content = "test content";
		$p->write();
		$vp = new VirtualPage();
		$vp->CopyContentFromID = $p->ID;
		$vp->write();

		// VP is oragne
		$this->assertTrue($vp->IsAddedToStage);

		// VP is still orange after we publish
		$p->doPublish();
		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->IsAddedToStage);
		
		// A new VP created after P's initial construction
		$vp2 = new VirtualPage();
		$vp2->CopyContentFromID = $p->ID;
		$vp2->write();
		$this->assertTrue($vp2->IsAddedToStage);
		
		// Also remains orange after a republish
		$p->Content = "new content";
		$p->write();
		$p->doPublish();
		$this->fixVersionNumberCache($vp2);
		$this->assertTrue($vp2->IsAddedToStage);
		
		// VP is now published
		$vp->doPublish();

		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->ExistsOnLive);
		$this->assertFalse($vp->IsModifiedOnStage);
		
		// P edited, VP and P both go green
		$p->Content = "third content";
		$p->write();

		$this->fixVersionNumberCache($vp, $p);
		$this->assertTrue($p->IsModifiedOnStage);
		$this->assertTrue($vp->IsModifiedOnStage);

		// Publish, VP goes black
		$p->doPublish();
		$this->fixVersionNumberCache($vp);
		$this->assertTrue($vp->ExistsOnLive);
		$this->assertFalse($vp->IsModifiedOnStage);
	}
	
	function fixVersionNumberCache($page) {
		$pages = func_get_args();
		foreach($pages as $p) {
			Versioned::prepopulate_versionnumber_cache('SiteTree', 'Stage', array($p->ID));
			Versioned::prepopulate_versionnumber_cache('SiteTree', 'Live', array($p->ID));
		}
	}
	
}