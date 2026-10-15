<?php

return [
    ['settings', 'cms/settings/1/edit', 'app.settings', 'fa-edit', [], false],    
    ['company', 'cms/company/1/edit', 'app.company', 'fa-briefcase', [], true],
    ['admins', 'cms/admins', 'CMS Admins', 'fa-unlock-alt', ['app.list' => '/', 'app.add' => '/create'], true],
    ['ai_posts', 'cms/ai-posts', 'Social Media', 'fa-network-wired', ['Posts' => '/', 'Content Planner' => '/plans', 'Calendar' => '/calendar', 'Accounts' => '/accounts', 'Create Post' => '/create'], true],
    ['blog', 'cms/blog', 'Blog', 'fa-book', ['app.list' => '/', 'app.add' => '/create'], true],
    ['newsletter', 'cms/newsletter', 'Newsletter', 'fa-envelope', ['app.list' => '/', 'app.add' => '/create'], true],
];