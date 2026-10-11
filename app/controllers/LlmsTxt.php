<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
/** Supplemental public guide, not an indexing or ranking mechanism. */
class LlmsTxt extends Controller {
    public function index() {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-cache');
        echo "# Forever Card Club (FCC)\n\n";
        echo "> Independent digital business tools for Forever Living Products partners. FCC is not an official Forever Living Products website or webshop.\n\n";
        echo "FCC connects personal partner cards, referral links, contact management, education, product information, AI assistance and engagement analytics. Product orders are placed in the official Forever webshop. Product information and AI assistance do not replace medical advice or guarantee business results.\n\n";
        echo "## Public resources\n";
        $links = [''=>'Home','pages/foreverclub'=>'FCC knowledge hub','page/forever-card-club'=>'What FCC is','page/how-it-works'=>'How it works','page/faq'=>'Frequently asked questions','page/about'=>'About FCC','pages/who-created-forever-card-club'=>'Who created FCC','page/smart-referral-links'=>'Referral links','page/ai-product-assistants'=>'AI product assistants','blog'=>'Products and practical guides','featured-apps'=>'Featured partner cards','recommended-sponsors'=>'Recommended sponsors'];
        if(!fcc_legal_pending()) $links += ['legal/privacy'=>'Privacy policy', 'legal/terms'=>'Terms of use and independent team system'];
        foreach($links as $path=>$label) echo '- ['.$label.']('.SITE_URL.$path.")\n";
        echo "\nPublic pages are the authoritative source. Private accounts, contacts, conversations, billing and administration are not public resources.\n";
    }
}
