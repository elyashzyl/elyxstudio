<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ElyxStudioSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Nebula Brand Kit',
                'slug' => 'nebula-brand-kit',
                'type' => 'digital',
                'category' => 'Digital assets',
                'tagline' => 'A launch-ready visual identity for ambitious teams.',
                'description' => 'A complete editable brand system for creators and startups who want to look established from day one.',
                'features' => ['Logo suite and favicon', 'Color and type system', 'Social launch templates', 'Editable source files'],
                'image_url' => 'https://images.unsplash.com/photo-1558655146-d09347e92766?auto=format&fit=crop&w=1200&q=85',
                'price' => 149,
                'compare_at_price' => 199,
                'stock' => null,
                'is_featured' => true,
            ],
            [
                'name' => 'Creator Launch Bundle',
                'slug' => 'creator-launch-bundle',
                'type' => 'digital',
                'category' => 'Digital assets',
                'tagline' => 'Everything you need to publish with confidence.',
                'description' => 'A focused collection of templates for creators building a consistent presence across the web.',
                'features' => ['20 social templates', 'Content planning board', 'Pitch deck template', 'Commercial license'],
                'image_url' => 'https://images.unsplash.com/photo-1545235617-9465d2a55698?auto=format&fit=crop&w=1200&q=85',
                'price' => 89,
                'compare_at_price' => 119,
                'stock' => null,
                'is_featured' => true,
            ],
            [
                'name' => 'Elyx Studio Notebook',
                'slug' => 'elyx-studio-notebook',
                'type' => 'product',
                'category' => 'Studio goods',
                'tagline' => 'A tactile home for your next big idea.',
                'description' => 'A premium studio notebook with a soft-touch cover, heavyweight paper, and space for ideas that deserve room.',
                'features' => ['160 dotted pages', 'Lay-flat binding', 'Recycled paper', 'Ships worldwide'],
                'image_url' => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?auto=format&fit=crop&w=1200&q=85',
                'price' => 28,
                'compare_at_price' => null,
                'stock' => 48,
                'is_featured' => true,
            ],
            [
                'name' => 'Starter Website',
                'slug' => 'starter-website',
                'type' => 'service',
                'category' => 'Websites',
                'tagline' => 'A sharp one-page website built around your offer.',
                'description' => 'A fast, focused website for a new offer, personal brand, or small business. Designed, built, and launched by ELYX Studio.',
                'features' => ['Strategy call', 'Custom one-page design', 'Responsive development', 'Launch support'],
                'image_url' => 'https://images.unsplash.com/photo-1467232004584-a241de8bcf5d?auto=format&fit=crop&w=1200&q=85',
                'price' => 1200,
                'compare_at_price' => null,
                'billing_period' => 'project',
                'stock' => null,
                'is_featured' => false,
            ],
            [
                'name' => 'Commerce Website',
                'slug' => 'commerce-website',
                'type' => 'service',
                'category' => 'Websites',
                'tagline' => 'A conversion-ready storefront made for growth.',
                'description' => 'A complete commerce experience with product architecture, checkout strategy, responsive UI, and a clean admin foundation.',
                'features' => ['Storefront strategy', 'Catalog and checkout UX', 'CMS-ready build', 'Analytics setup'],
                'image_url' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?auto=format&fit=crop&w=1200&q=85',
                'price' => 3200,
                'compare_at_price' => null,
                'billing_period' => 'project',
                'stock' => null,
                'is_featured' => false,
            ],
            [
                'name' => 'Digital Product Sprint',
                'slug' => 'digital-product-sprint',
                'type' => 'service',
                'category' => 'Strategy',
                'tagline' => 'Turn a promising idea into a buildable product plan.',
                'description' => 'A focused design sprint for defining the audience, offer, product structure, and first release before development starts.',
                'features' => ['Product discovery workshop', 'User journey map', 'MVP feature plan', 'Delivery roadmap'],
                'image_url' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=85',
                'price' => 950,
                'compare_at_price' => null,
                'billing_period' => 'sprint',
                'stock' => null,
                'is_featured' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['slug' => $product['slug']], [
                ...$product,
                'is_active' => true,
            ]);
        }
    }
}
