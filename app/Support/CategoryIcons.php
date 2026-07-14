<?php

namespace App\Support;

class CategoryIcons
{
    /**
     * A curated set of FontAwesome solid icon classes admins can assign to a
     * category, covering the categories most marketplaces carry. Returned as
     * [value => label] for a Filament Select.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'fa-solid fa-tv' => 'Electronics (TV)',
            'fa-solid fa-mobile-screen' => 'Mobile Phones',
            'fa-solid fa-laptop' => 'Computers & Laptops',
            'fa-solid fa-blender' => 'Home Appliances',
            'fa-solid fa-couch' => 'Furniture',
            'fa-solid fa-kitchen-set' => 'Kitchenware',
            'fa-solid fa-shirt' => "Men's Fashion",
            'fa-solid fa-vest' => "Women's Fashion",
            'fa-solid fa-baby' => 'Kids & Baby',
            'fa-solid fa-shoe-prints' => 'Shoes',
            'fa-solid fa-suitcase' => 'Bags & Luggage',
            'fa-solid fa-gem' => 'Watches & Jewelry',
            'fa-solid fa-spa' => 'Beauty & Personal Care',
            'fa-solid fa-heart-pulse' => 'Health & Wellness',
            'fa-solid fa-basketball' => 'Sports & Outdoors',
            'fa-solid fa-puzzle-piece' => 'Toys & Games',
            'fa-solid fa-book' => 'Books & Stationery',
            'fa-solid fa-car' => 'Automotive',
            'fa-solid fa-toolbox' => 'Tools & Hardware',
            'fa-solid fa-paw' => 'Pet Supplies',
            'fa-solid fa-basket-shopping' => 'Groceries',
            'fa-solid fa-briefcase' => 'Office Supplies',
            'fa-solid fa-music' => 'Musical Instruments',
            'fa-solid fa-seedling' => 'Garden & Outdoor',
            'fa-solid fa-gamepad' => 'Video Games',
            'fa-solid fa-utensils' => 'Food & Beverages',
            'fa-solid fa-house' => 'Home & Living',
            'fa-solid fa-dumbbell' => 'Fitness',
            'fa-solid fa-camera' => 'Cameras & Photography',
            'fa-solid fa-headphones' => 'Audio',
            'fa-solid fa-tag' => 'General / Other',
        ];
    }

    public static function default(): string
    {
        return 'fa-solid fa-tag';
    }
}
