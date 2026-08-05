<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * One row per automated email — see docs/modules/M09-notices-communication.md's
 * "Seeded templates" table. `EmailTemplateRenderer` refuses to render a key
 * that isn't here, same guard shape as SettingsSeeder/SettingsRepository.
 *
 * `donation.receipt`, `donation.failed`, `receipt.80g`, `subscription.charged`
 * and `member.welcome` are seeded per the module doc's table even though no
 * Mailable sends them yet in this codebase — they're future hooks (e.g. a
 * dedicated 80G-issued email separate from the donation thank-you) the admin
 * UI should already list.
 */
class EmailTemplateSeeder extends Seeder
{
    /** @var list<array{key: string, name: string, subject: string, body_html: string, available_variables: list<string>, send_copy_to_admin?: bool}> */
    private const TEMPLATES = [
        [
            'key' => 'donation.thank_you',
            'name' => 'Donation thank-you',
            'subject' => 'Thank you for your donation — receipt {{ receipt_number }}',
            'body_html' => "<h1 style=\"font-size: 18px; margin: 0 0 16px;\">Thank you for your donation</h1>\n<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your donation of ₹{{ amount }} means a lot to us. Your receipt (<strong>{{ receipt_number }}</strong>) is attached to this email.</p>",
            'available_variables' => ['donor_name', 'amount', 'receipt_number', 'org_name'],
        ],
        [
            'key' => 'donation.receipt',
            'name' => 'Donation receipt attached',
            'subject' => 'Your receipt {{ receipt_number }} is attached',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your receipt <strong>{{ receipt_number }}</strong> for ₹{{ amount }} is attached to this email.</p>",
            'available_variables' => ['donor_name', 'amount', 'receipt_number', 'org_name'],
        ],
        [
            'key' => 'donation.failed',
            'name' => 'Donation payment failed',
            'subject' => "We couldn't process your donation",
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your donation of ₹{{ amount }} could not be processed. No amount was charged — please try again whenever you're ready.</p>",
            'available_variables' => ['donor_name', 'amount', 'org_name'],
        ],
        [
            'key' => 'receipt.80g',
            'name' => '80G receipt issued',
            'subject' => 'Your 80G receipt {{ receipt_number }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your 80G tax-exemption receipt <strong>{{ receipt_number }}</strong> is attached to this email.</p>",
            'available_variables' => ['donor_name', 'receipt_number', 'org_name'],
        ],
        [
            'key' => 'subscription.activated',
            'name' => 'Monthly donation activated',
            'subject' => 'Your monthly donation is set up',
            'body_html' => "<h1 style=\"font-size: 18px; margin: 0 0 16px;\">Your monthly donation is set up</h1>\n<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Thank you — your monthly donation of ₹{{ amount }} is now active. Your next charge is on {{ next_charge_date }}.</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">You can view, pause, or cancel this any time from your donor portal.</p>",
            'available_variables' => ['donor_name', 'amount', 'next_charge_date', 'org_name'],
        ],
        [
            'key' => 'subscription.charged',
            'name' => 'Monthly donation charged',
            'subject' => 'Your monthly donation was successful',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">This month's donation of ₹{{ amount }} went through — thank you for your continued support.</p>",
            'available_variables' => ['donor_name', 'amount', 'org_name'],
        ],
        [
            'key' => 'subscription.charge_failed',
            'name' => 'Monthly donation charge failed',
            'subject' => "We couldn't process this month's donation",
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">This month's donation of ₹{{ amount }} could not be processed ({{ reason }}). This usually happens on the bank's side — please check your payment method is still valid, or update it any time from your portal.</p>",
            'available_variables' => ['donor_name', 'amount', 'reason', 'org_name'],
        ],
        [
            'key' => 'subscription.halted',
            'name' => 'Monthly donation halted',
            'subject' => 'Your monthly donation has been paused',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">After a few unsuccessful attempts, we've paused your monthly donation of ₹{{ amount }}. No further charges will happen automatically — reach out any time to restart it.</p>",
            'available_variables' => ['donor_name', 'amount', 'org_name'],
        ],
        [
            'key' => 'subscription.cancelled',
            'name' => 'Monthly donation cancelled',
            'subject' => 'Your monthly donation has been cancelled',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your monthly donation of ₹{{ amount }} has been cancelled. Thank you for everything you've given so far.</p>",
            'available_variables' => ['donor_name', 'amount', 'org_name'],
        ],
        [
            'key' => 'member.welcome',
            'name' => 'Member account created',
            'subject' => 'Welcome to {{ org_name }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ member_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your member account is ready. Sign in to your portal any time to view your documents and donation history.</p>",
            'available_variables' => ['member_name', 'org_name'],
        ],
        [
            'key' => 'member.id_card_issued',
            'name' => 'Member ID card issued',
            'subject' => 'Your member ID card — {{ document_number }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ member_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your member ID card (<strong>{{ document_number }}</strong>) is ready — you can download it any time from your portal.</p>",
            'available_variables' => ['member_name', 'document_number', 'org_name'],
        ],
        [
            'key' => 'member.appointment_letter',
            'name' => 'Appointment letter issued',
            'subject' => 'Your appointment letter — {{ document_number }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ member_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your appointment letter (<strong>{{ document_number }}</strong>) is ready — you can download it any time from your portal.</p>",
            'available_variables' => ['member_name', 'document_number', 'org_name'],
        ],
        [
            'key' => 'member.certificate_issued',
            'name' => 'Certificate issued',
            'subject' => 'Your certificate — {{ document_title }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ member_name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Your certificate \"{{ document_title }}\" (<strong>{{ document_number }}</strong>) is ready — you can download it any time from your portal.</p>",
            'available_variables' => ['member_name', 'document_number', 'document_title', 'org_name'],
        ],
        [
            'key' => 'notice.default',
            'name' => 'Notice email wrapper',
            'subject' => '{{ notice_title }}',
            'body_html' => "<h1 style=\"font-size: 18px; margin: 0 0 16px;\">{{ notice_title }}</h1>\n{{ notice_body }}\n<p style=\"font-size: 13px; margin-top: 16px;\"><a href=\"{{ portal_url }}\">View in your portal</a></p>",
            'available_variables' => ['notice_title', 'notice_body', 'portal_url', 'org_name'],
        ],
        [
            'key' => 'campaign.update',
            'name' => 'Campaign update published',
            'subject' => 'Update on {{ campaign_title }}',
            'body_html' => "<h1 style=\"font-size: 18px; margin: 0 0 16px;\">{{ update_title }}</h1>\n<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ donor_name }},</p>\n{{ update_body }}",
            'available_variables' => ['donor_name', 'campaign_title', 'update_title', 'update_body', 'org_name'],
        ],
        [
            'key' => 'fundraiser.received',
            'name' => 'Fundraiser request acknowledged',
            'subject' => "We've received your fundraiser request",
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Thanks for submitting \"{{ title }}\" — our team will review it and get back to you shortly.</p>",
            'available_variables' => ['name', 'title', 'org_name'],
        ],
        [
            'key' => 'fundraiser.approved',
            'name' => 'Fundraiser request approved',
            'subject' => 'Your fundraiser request has been approved',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Good news — \"{{ title }}\" has been approved. We've created a draft campaign for it in the admin, and our team will be in touch about publishing it.</p>",
            'available_variables' => ['name', 'title', 'org_name'],
        ],
        [
            'key' => 'fundraiser.rejected',
            'name' => 'Fundraiser request rejected',
            'subject' => 'Update on your fundraiser request',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Hi {{ name }},</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">After review, we're not able to move forward with \"{{ title }}\" at this time. Reason: {{ reason }}</p>",
            'available_variables' => ['name', 'title', 'reason', 'org_name'],
        ],
        [
            'key' => 'fundraiser.new_request',
            'name' => 'New fundraiser request (admin)',
            'subject' => 'New fundraiser request: {{ title }}',
            'body_html' => '<p style="font-size: 14px; line-height: 1.6;">{{ name }} submitted a new fundraiser request "{{ title }}" with a goal of ₹{{ goal_amount }}. Review it in the admin panel.</p>',
            'available_variables' => ['name', 'title', 'goal_amount', 'org_name'],
        ],
        [
            'key' => 'contact.new_message',
            'name' => 'New contact form message (admin)',
            'subject' => 'New contact form message: {{ subject_line }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\"><strong>{{ name }}</strong> ({{ email }}) sent:</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">{{ message }}</p>",
            'available_variables' => ['name', 'email', 'subject_line', 'message', 'org_name'],
        ],
        [
            'key' => 'csr.new_inquiry',
            'name' => 'New CSR partnership inquiry (admin)',
            'subject' => 'New CSR partnership inquiry: {{ organisation_name }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\"><strong>{{ contact_name }}</strong> from <strong>{{ organisation_name }}</strong> ({{ email }}) is interested in a CSR partnership:</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">{{ message }}</p>",
            'available_variables' => ['organisation_name', 'contact_name', 'email', 'message', 'org_name'],
        ],
        [
            'key' => 'internship.new_application',
            'name' => 'New internship application (admin)',
            'subject' => 'New internship application: {{ name }}',
            'body_html' => '<p style="font-size: 14px; line-height: 1.6;"><strong>{{ name }}</strong> ({{ email }}) applied for an internship — track: {{ track }}. Review it in the admin panel.</p>',
            'available_variables' => ['name', 'email', 'track', 'org_name'],
        ],
        [
            'key' => 'newsletter.confirm',
            'name' => 'Newsletter double opt-in',
            'subject' => 'Confirm your subscription to {{ org_name }}',
            'body_html' => "<p style=\"font-size: 14px; line-height: 1.6;\">Thanks for subscribing! Please confirm your email address to start receiving updates.</p>\n<p style=\"font-size: 14px; line-height: 1.6;\"><a href=\"{{ confirm_url }}\">Confirm my subscription</a></p>",
            'available_variables' => ['confirm_url', 'org_name'],
        ],
        [
            'key' => 'reports.monthly_summary',
            'name' => 'Monthly summary (admin)',
            'subject' => '{{ org_name }} — summary for {{ month }}',
            'body_html' => "<h1 style=\"font-size: 18px; margin: 0 0 16px;\">Summary for {{ month }}</h1>\n<p style=\"font-size: 14px; line-height: 1.6;\">Total raised: <strong>₹{{ total }}</strong> from {{ donor_count }} donor(s).</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">New recurring donors: {{ new_recurring_donors }}. Cancelled subscriptions: {{ churned_subscriptions }}.</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">Top campaign: {{ top_campaign }}.</p>\n<p style=\"font-size: 14px; line-height: 1.6;\">{{ attention_summary }}</p>",
            'available_variables' => ['month', 'total', 'donor_count', 'new_recurring_donors', 'churned_subscriptions', 'top_campaign', 'attention_summary', 'org_name'],
        ],
    ];

    public function run(): void
    {
        foreach (self::TEMPLATES as $template) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body_html' => $template['body_html'],
                    'available_variables' => $template['available_variables'],
                    'is_active' => true,
                    'send_copy_to_admin' => $template['send_copy_to_admin'] ?? false,
                ]
            );
        }
    }
}
