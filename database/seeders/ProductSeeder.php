<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'title' => 'Silver Chrome Star Pendant Necklace',
                'title_khmer' => 'ខ្សែករូបផ្កាយប្រាក់ Y2K Cyber',
                'category' => 'jewelry',
                'category_label' => 'Y2K Jewelry',
                'price_usd' => 6.50,
                'price_khr' => 26650,
                'stock' => 45,
                'image' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=700&q=80',
                'badge' => 'Staff pick',
                'material' => '316L Stainless Steel',
                'color' => 'Chrome Silver',
                'description' => 'Cyberpunk star motif with layered curb chain. 100% water resistant and tarnish-free.',
            ],
            [
                'title' => 'Chunky Cyber Y2K Silver Ring Set (4pcs)',
                'title_khmer' => 'ឈុតចិញ្ចៀនប្រាក់ Cyberpunk (៤ វង់)',
                'category' => 'jewelry',
                'category_label' => 'Y2K Jewelry',
                'price_usd' => 5.00,
                'price_khr' => 20500,
                'stock' => 60,
                'image' => 'https://images.unsplash.com/photo-1605100804763-247f67b3557e?auto=format&fit=crop&w=700&q=80',
                'badge' => 'New',
                'material' => 'Titanium Alloy',
                'color' => 'Polished Silver',
                'description' => 'Stackable geometric rings designed for unisex streetwear styling.',
            ],
            [
                'title' => 'Vintage 90s Tinted Oval Sunglasses',
                'title_khmer' => 'វ៉ែនតាពណ៍ស្រាលម៉ូដទសវត្សរ៍ ៩០',
                'category' => 'eyewear',
                'category_label' => 'Retro Shades',
                'price_usd' => 7.50,
                'price_khr' => 30750,
                'stock' => 35,
                'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?auto=format&fit=crop&w=700&q=80',
                'badge' => 'New',
                'material' => 'UV400 Polycarbonate',
                'color' => 'Tea Brown & Gold Rim',
                'description' => 'Minimalist slim oval wireframe with UV400 protective tint.',
            ],
            [
                'title' => 'Futuristic Rimless Wrap-Around Shades',
                'title_khmer' => 'វ៉ែនតាម៉ូដកាលីបទាន់សម័យ Y2K Matrix',
                'category' => 'eyewear',
                'category_label' => 'Retro Shades',
                'price_usd' => 8.00,
                'price_khr' => 32800,
                'stock' => 25,
                'image' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=700&q=80',
                'badge' => 'New',
                'material' => 'Shatterproof PC',
                'color' => 'Silver Mirror Lens',
                'description' => 'Matrix-inspired aerodynamic streetwear wrap silhouette.',
            ],
            [
                'title' => 'Puffy Cloud Dumpling Nylon Shoulder Bag',
                'title_khmer' => 'កាបូបស្ពាយសាច់ទន់ Puffy Dumpling',
                'category' => 'bags',
                'category_label' => 'Cloud Bags',
                'price_usd' => 12.00,
                'price_khr' => 49200,
                'stock' => 30,
                'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=700&q=80',
                'badge' => 'Staff pick',
                'material' => 'Waterproof Padded Nylon',
                'color' => 'Matcha Cream',
                'description' => 'Super lightweight, quilted cloud texture with secure zip closure.',
            ],
            [
                'title' => 'Mini Boxy Y2K Metallic Crossbody Bag',
                'title_khmer' => 'កាបូបតូចស្ពាយឆៀងសាច់ប្រាក់រលោង',
                'category' => 'bags',
                'category_label' => 'Cloud Bags',
                'price_usd' => 11.50,
                'price_khr' => 47150,
                'stock' => 22,
                'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=700&q=80',
                'badge' => 'New',
                'material' => 'Metallic Vegan Leather',
                'color' => 'Chrome Silver',
                'description' => 'Compact crossbody fits iPhone Pro Max, cardholder, and lip gloss.',
            ],
            [
                'title' => 'Pastel Matte French Hair Claw Clip Set (3pcs)',
                'title_khmer' => 'ដង្កៀបសក់ពណ៌ pastel ម៉ូដបារាំង (៣ គ្រឿង)',
                'category' => 'hair',
                'category_label' => 'Claw Clips',
                'price_usd' => 3.50,
                'price_khr' => 14350,
                'stock' => 80,
                'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=700&q=80',
                'badge' => 'Staff pick',
                'material' => 'Biodegradable Acetate',
                'color' => 'Sage, Milk Cream, Matcha',
                'description' => 'Non-slip spring grip suitable for all hair types.',
            ],
            [
                'title' => 'Handmade Beaded Phone Charm Wristlet',
                'title_khmer' => 'ខ្សែចងទូរស័ព្ទអង្កាំត្បូង Handmade គួរឱ្យស្រលាញ់',
                'category' => 'charms',
                'category_label' => 'Phone Charms',
                'price_usd' => 4.00,
                'price_khr' => 16400,
                'stock' => 95,
                'image' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?auto=format&fit=crop&w=700&q=80',
                'badge' => 'Staff pick',
                'material' => 'Glass Beads & Strong Nylon Cord',
                'color' => 'Pastel Stars & Freshwater Pearls',
                'description' => 'Durable phone lanyard tethered with Kawaii acrylic stars.',
            ],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['title' => $p['title']],
                array_merge($p, [
                    'slug' => Str::slug($p['title']),
                    'status' => 'active',
                ])
            );
        }
    }
}
