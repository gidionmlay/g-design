<?php
/**
 * G DESIGN - canonical service catalog (M0 seed source).
 *
 * Keep in sync with the QUOTE_CONFIG fallback in public/assets/js/quote.js.
 */
return [
    [
        'slug' => 'branding',
        'name' => 'Branding',
        'tag' => 'BRANDING',
        'description' => 'Identities that are recognizable and consistent across every touchpoint.',
        'image_path' => 'assets/images/service/01.webp',
        'items' => [
            [
                'slug' => 'brand-identity',
                'name' => 'Brand Identity',
                'description' => 'Full identity systems and refresh work.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'scope',
                        'label' => 'Scope',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['New identity', 'Refresh existing', 'Logo + stationery package'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'brand_name',
                        'label' => 'Brand / business name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Your brand name',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'style',
                        'label' => 'Preferred style direction',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. bold, elegant, playful…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'brand-guidelines',
                'name' => 'Brand Guidelines',
                'description' => 'Rules that keep your brand consistent.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'status',
                        'label' => 'Status',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Brand already exists', 'Starting fresh'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'includes',
                        'label' => 'Include',
                        'type' => 'checkbox',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Logo usage', 'Colour system', 'Typography', 'Stationery templates'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'notes',
                        'label' => 'Notes / scope',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Anything specific the guidelines should cover…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'brand-strategy',
                'name' => 'Brand Strategy',
                'description' => 'Positioning, messaging and roadmap.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'bs_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'bs_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'bs_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'bs_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'slug' => 'graphic-design',
        'name' => 'Graphic Design',
        'tag' => 'GRAPHIC DESIGN',
        'description' => 'Visual communication for businesses, campaigns and individuals.',
        'image_path' => 'assets/images/service/02.webp',
        'items' => [
            [
                'slug' => 'logo-design',
                'name' => 'Logo Design & Logo Animation',
                'description' => 'New logo, brand packages and logo animation.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'logo_name',
                        'label' => 'Logo name',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'The name your logo should display',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'package',
                        'label' => 'Design Package',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['New Logo Design & Brand Package', 'New Design & Animation', 'Animation Only'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'colour_style',
                        'label' => 'Preferred Colour / Style',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. Modern, minimal, orange accent',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'business',
                        'label' => 'Company / Business Activities',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'What does your business do?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 700,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'flyer-brochures',
                'name' => 'Flyer',
                'description' => 'Flyers for events, promotions and more.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below 100', '100 – 500', 'Above 500'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'size',
                        'label' => 'Print size',
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['A6', 'A5', 'A4', 'A3'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'service',
                        'label' => 'Service type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'sides',
                        'label' => 'Sides',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['One side', 'Double side'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'poster-design',
                'name' => 'Poster Design',
                'description' => 'Poster design and graphics animation.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Poster design', 'Graphics animation'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'Number of posters',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'brochure',
                'name' => 'Brochure Design',
                'description' => 'Multi-page print or digital brochures.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Brochure type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Bi-fold', 'Tri-fold', 'Multi-page'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'pages',
                        'label' => 'Number of pages',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below 100', '100 – 500', 'Above 500'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'size',
                        'label' => 'Size',
                        'type' => 'select',
                        'required' => false,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['A4', 'A3'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'service',
                        'label' => 'Service',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Design only', 'Design & print', 'Print only'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'social-media',
                'name' => 'Social Media Designs',
                'description' => 'Posts, stories and covers for your channels.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'platforms',
                        'label' => 'Platforms',
                        'type' => 'checkbox',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Instagram', 'Facebook', 'LinkedIn', 'X', 'TikTok', 'WhatsApp'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'formats',
                        'label' => 'Formats',
                        'type' => 'checkbox',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Posts', 'Stories', 'Reels covers', 'Profile & cover'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'count',
                        'label' => 'Number of designs',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'style',
                        'label' => 'Style',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Template-based', 'Custom from scratch'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'business-cards',
                'name' => 'Business Cards',
                'description' => 'Standard and premium finish cards.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'service',
                        'label' => 'Service type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['100', '200', '300', 'Above 300'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'material',
                        'label' => 'Material type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Soft touch gloss paper', 'Lamination card', 'PVC'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'sides',
                        'label' => 'Print side',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['One side', 'Both side (front and back)'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'Above TZS 300,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'certificate-calendar',
                'name' => 'Certificate & Calendar',
                'description' => 'Award certificates and wall or desk calendars.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Certificate', 'Calendar'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below 50 pcs', '50 pcs to 200 pcs', 'Above 200 pcs'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'size',
                        'label' => 'Print size',
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['A4', 'A3'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'service',
                        'label' => 'Service type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'sides',
                        'label' => 'Sides',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['One side'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'TZS 300,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'menu-design',
                'name' => 'Menu Design',
                'description' => 'Menus for restaurants, cafes and bars — design and print.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'Number of menus',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'size',
                        'label' => 'Print size',
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['A5', 'A4', 'A3'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'service',
                        'label' => 'Service type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'sides',
                        'label' => 'Sides',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['One side', 'Two side'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'company-profile',
                'name' => 'Company Profile',
                'description' => 'Professional company profiles for print and sharing.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'Number of printed copies',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'pages',
                        'label' => 'Total pages',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'Number of pages you want',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'size',
                        'label' => 'Print size',
                        'type' => 'select',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['A4', 'A3'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'service',
                        'label' => 'Service type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'sides',
                        'label' => 'Sides',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['One side'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'TZS 300,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'slug' => 'printing',
        'name' => 'Printing',
        'tag' => 'PRINTING',
        'description' => 'From digital designs to physical products — banners, apparel and more.',
        'image_path' => 'assets/images/service/03.webp',
        'items' => [
            [
                'slug' => 'banner',
                'name' => 'Banner Printing',
                'description' => 'Flex, roll-up, mesh and tear drop banner printing.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'product',
                        'label' => 'Banner type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Flex Banner', 'Roll-up Banner', 'Mesh Banner (wind resistant)', 'Tear Drop / Flying Banner'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => 'Number of banners',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'width',
                        'label' => 'Width',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => 'metres / cm',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'height',
                        'label' => 'Height',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => 'metres / cm',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'service',
                        'label' => 'Service needed',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design', 'Printing & installation', 'Print, design & installation'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'environment',
                        'label' => 'Usage environment',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Outdoor', 'Indoor'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 6,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'sticker-labels',
                'name' => 'Sticker & Labels',
                'description' => 'Custom diecut, circle and square stickers in various materials.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'shape',
                        'label' => 'Sticker shape',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Custom Diecut (custom contour)', 'Circle / round', 'Square / rectangle'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'width',
                        'label' => 'Width (cm)',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => 'e.g. 10',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'height',
                        'label' => 'Height (cm)',
                        'type' => 'number',
                        'required' => false,
                        'placeholder' => 'e.g. 20',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'service',
                        'label' => 'Service need',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Print only', 'Print & design', 'Print & installation'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'material',
                        'label' => 'Sticker material',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Waterproof vinyl (white)', 'Transparent / clear vinyl', 'Paper label sticker'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 6,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 200,000/=', 'TZS 200,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'tshirt-cap',
                'name' => 'T-Shirt & Kofia Printing',
                'description' => 'DTF, embroidery and vinyl on apparel.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'garment',
                        'label' => 'Garment type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Round Neck', 'Polo', 'Sport Jersey', 'Reflective Vest', 'Long Sleeve', 'Pullover', 'Cap (Kofia)'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'printing',
                        'label' => 'Printing type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['DTF Full Colour', 'Embroidery (stitched logo)', 'Vinyl Heat Transfer'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'colour',
                        'label' => 'Colour',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. Black, White, Royal Blue',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'size_breakdown',
                        'label' => 'Size breakdown',
                        'type' => 'sizegrid',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => ['S', 'M', 'L', 'XL', 'XXL'],
                        'show_when' => null,
                        'one_size_when' => [
                            'key' => 'garment',
                            'value' => 'Cap (Kofia)',
                            'label' => 'One Size'
                        ]
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 5,
                        'field_key' => 'location',
                        'label' => 'Print location',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Front', 'Back', 'Front & Back'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 6,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 150,000/=', 'TZS 150,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 1,000,000/=', 'Above TZS 1,000,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'mug-bottle',
                'name' => 'Mug & Bottle Printing',
                'description' => 'Full wrap or logo printing on drinkware.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'product',
                        'label' => 'Product type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Ceramic mug', 'Magic Mug / Color changing Mug', 'Aluminum water bottle', 'Plastic water bottle'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'printing_method',
                        'label' => 'Printing method',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Sublimation', 'UV Printing'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'quantity',
                        'label' => 'Quantity',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 100,000/=', 'TZS 100,000/= to TZS 500,000/=', 'TZS 500,000/= to TZS 500,000/=', 'Above TZS 500,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'photo-printing',
                'name' => 'Photo Printing',
                'description' => 'High quality prints in multiple sizes.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'print_type',
                        'label' => 'Print type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Wooden photoframe (picha mbao)', 'Canvas photo print', 'Photo clock'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'sizes',
                        'label' => 'Sizes & quantities',
                        'type' => 'sizegrid',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => ['A5', 'A4', 'A3', 'A2', 'A1'],
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'more_explanation',
                        'label' => 'More explanation',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Any special requirements, frame preferences, orientation, etc.',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'budget',
                        'label' => 'Estimated budget',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Below TZS 50,000/=', 'TZS 50,000/= to TZS 100,000/=', 'TZS 100,000/= to TZS 300,000/=', 'Above TZS 300,000/='],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'slug' => 'content-creation',
        'name' => 'Content Creation',
        'tag' => 'CONTENT CREATION',
        'description' => 'Photography, video and motion content that tells your story.',
        'image_path' => 'assets/images/service/04.webp',
        'items' => [
            [
                'slug' => 'photography',
                'name' => 'Photography',
                'description' => 'Product, event, corporate and portrait shoots.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Session type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Product', 'Event', 'Corporate', 'Portrait', 'Real estate'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'duration',
                        'label' => 'Duration',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. 3 hours, half day',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'location',
                        'label' => 'Location',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'Where is the shoot?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'edited',
                        'label' => 'Number of edited photos needed',
                        'type' => 'number',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 4,
                        'field_key' => 'extras',
                        'label' => 'Extras',
                        'type' => 'checkbox',
                        'required' => false,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Prints', 'Video reel'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'videography',
                'name' => 'Videography',
                'description' => 'Event, corporate and promo videos.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Video type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Event', 'Corporate', 'Promo', 'Documentary', 'Interview'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'duration',
                        'label' => 'Duration estimate',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. 5 minute video',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'location',
                        'label' => 'Filming location',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'editing',
                        'label' => 'Editing',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Full edit included', 'Raw footage only'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'video-editing',
                'name' => 'Video Editing',
                'description' => 'Edit your footage for any platform.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'footage',
                        'label' => 'Source material',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Footage provided', 'Need filming too'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'length',
                        'label' => 'Target length',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. 60 seconds',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'platforms',
                        'label' => 'Platforms',
                        'type' => 'checkbox',
                        'required' => false,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['YouTube', 'Instagram', 'TikTok', 'TV'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'motion-graphics',
                'name' => 'Motion Graphics',
                'description' => 'Logo animation, explainers and social motion.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'type',
                        'label' => 'Type',
                        'type' => 'radio',
                        'required' => true,
                        'placeholder' => null,
                        'hint' => null,
                        'options' => ['Logo animation', 'Explainer video', 'Intro & outro', 'Social animations'],
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'duration',
                        'label' => 'Duration',
                        'type' => 'text',
                        'required' => true,
                        'placeholder' => 'e.g. 15 seconds',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'style',
                        'label' => 'Style reference',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Describe or link to references',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'slug' => 'creative-strategy',
        'name' => 'Creative Strategy',
        'tag' => 'CREATIVE STRATEGY',
        'description' => 'Determining what to communicate and how.',
        'image_path' => 'assets/images/service/03.webp',
        'items' => [
            [
                'slug' => 'brand-strategy',
                'name' => 'Brand Strategy',
                'description' => 'Positioning, messaging and roadmap.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'cs_brand_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'cs_brand_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'cs_brand_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'cs_brand_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'content-strategy',
                'name' => 'Content Strategy',
                'description' => 'Content calendars, plans and systems.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'cs_content_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'cs_content_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'cs_content_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'cs_content_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'campaign-strategy',
                'name' => 'Campaign Strategy',
                'description' => 'Campaign planning from goal to execution.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'cs_campaign_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'cs_campaign_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'cs_campaign_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'cs_campaign_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'communication-direction',
                'name' => 'Communication Direction',
                'description' => 'How your brand speaks to the world.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'cs_comm_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'cs_comm_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'cs_comm_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'cs_comm_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ],
    [
        'slug' => 'art-direction',
        'name' => 'Art Direction',
        'tag' => 'ART DIRECTION',
        'description' => 'Consistent, professionally executed visual communication.',
        'image_path' => 'assets/images/service/01.webp',
        'items' => [
            [
                'slug' => 'campaign-art-direction',
                'name' => 'Campaign Art Direction',
                'description' => 'Look and feel for campaigns.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'ad_campaign_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'ad_campaign_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'ad_campaign_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'ad_campaign_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'brand-visual-direction',
                'name' => 'Brand Visual Direction',
                'description' => 'Visual consistency across outputs.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'ad_brand_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'ad_brand_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'ad_brand_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'ad_brand_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'photography-direction',
                'name' => 'Photography Direction',
                'description' => 'Direction for photo projects.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'ad_photo_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'ad_photo_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'ad_photo_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'ad_photo_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ],
            [
                'slug' => 'creative-direction',
                'name' => 'Creative Direction',
                'description' => 'Overall creative leadership.',
                'fields' => [
                    [
                        'sort_order' => 0,
                        'field_key' => 'ad_creative_goal',
                        'label' => 'Project overview / goal',
                        'type' => 'textarea',
                        'required' => true,
                        'placeholder' => 'Tell us about the project',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 1,
                        'field_key' => 'ad_creative_audience',
                        'label' => 'Target audience',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'Who is this for?',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 2,
                        'field_key' => 'ad_creative_deliverables',
                        'label' => 'Expected deliverables',
                        'type' => 'textarea',
                        'required' => false,
                        'placeholder' => 'e.g. brand guidelines, campaign plan…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ],
                    [
                        'sort_order' => 3,
                        'field_key' => 'ad_creative_channels',
                        'label' => 'Channels (if applicable)',
                        'type' => 'text',
                        'required' => false,
                        'placeholder' => 'e.g. Instagram, website, print…',
                        'hint' => null,
                        'options' => null,
                        'sizes' => null,
                        'show_when' => null,
                        'one_size_when' => null
                    ]
                ]
            ]
        ]
    ]
];
