<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Blog category — a PHP enum cast, not free text. See
 * docs/06-UI-UX-FOUNDATION.md §7: free text ("Education" / "education" /
 * "Educaton") silently breaks the blog category filter.
 */
enum PostCategory: string
{
    case ImpactStories = 'impact_stories';
    case Announcements = 'announcements';
    case FundraisingTips = 'fundraising_tips';
    case Events = 'events';
    case Volunteering = 'volunteering';
    case PressReleases = 'press_releases';

    public function label(): string
    {
        return match ($this) {
            self::ImpactStories => 'Impact Stories',
            self::Announcements => 'Announcements',
            self::FundraisingTips => 'Fundraising Tips',
            self::Events => 'Events',
            self::Volunteering => 'Volunteering',
            self::PressReleases => 'Press Releases',
        };
    }
}
