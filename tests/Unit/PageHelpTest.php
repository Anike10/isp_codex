<?php

namespace Tests\Unit;

use App\Support\PageHelp;
use Tests\TestCase;

class PageHelpTest extends TestCase
{
    public function test_it_combines_module_and_page_type_guidance(): void
    {
        $guide = PageHelp::forRoute('products.index');

        $this->assertStringContainsString('পণ্য ও ইনভেন্টরি', $guide['title']);
        $this->assertStringContainsString('তালিকা ও অনুসন্ধান', $guide['title']);
        $this->assertNotEmpty($guide['features']);
        $this->assertNotEmpty($guide['steps']);
        $this->assertNotEmpty($guide['notes']);
    }

    public function test_specific_page_guidance_overrides_generic_content(): void
    {
        $guide = PageHelp::forRoute('mikrotik-routers.compare');

        $this->assertSame('App ও Live MikroTik তুলনা/সিঙ্ক', $guide['title']);
        $this->assertStringContainsString('PPP Profile', $guide['intro']);
    }

    public function test_pipe_separated_patterns_match_each_listed_route(): void
    {
        $invoiceGuide = PageHelp::forRoute('invoices.create');
        $quotationGuide = PageHelp::forRoute('quotations.edit');

        $this->assertSame('ইনভয়েস/কোটেশন ফর্ম', $invoiceGuide['title']);
        $this->assertSame($invoiceGuide['title'], $quotationGuide['title']);
    }

    public function test_unknown_routes_still_receive_complete_fallback_guidance(): void
    {
        $guide = PageHelp::forRoute('future-module.new-screen');

        $this->assertNotEmpty($guide['title']);
        $this->assertCount(3, $guide['features']);
        $this->assertCount(3, $guide['steps']);
        $this->assertCount(2, $guide['notes']);
    }
}
