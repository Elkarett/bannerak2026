import os
import re

base_dir = "/Users/carlosdiez/Sites/banners/Banner2026/enviogratis"

source_brand = "heineken"
source_slug = "/gastosheineken/"
source_tracking = "gg_HEINEKEN_2Q_125"
source_image_part = "heineken-202601q2"
source_price = "15€"

configs = [
    {
        "brand": "kimberly",
        "brand_display": "Kimberly",
        "slug_replacement": "/kimber/",
        "tracking_replacement": "gg_KIMBERLY_1Q_225",
        "price_replacement": "25€",
        "target_filename_part": "kimberly-2026021q",
        "image_replacement_part": "kimberly-2026021q"
    },
    {
        "brand": "sodalis",
        "brand_display": "Sodalis",
        "slug_replacement": "/sodalispromo/",
        "tracking_replacement": "gg_SODALIS_1Q_225",
        "price_replacement": "15€", # Same as source
        "target_filename_part": "sodalis-2026021q",
        "image_replacement_part": "sodalis-2026021q"
    },
    {
        "brand": "pepsico",
        "brand_display": "Pepsico",
        "slug_replacement": "/gastospepsi/",
        "tracking_replacement": "gg_PEPSICO_1Q_225",
        "price_replacement": "15€", # Same as source
        "target_filename_part": "pepsico-2026021q",
        "image_replacement_part": "pepsico-2026021q"
    }
]

languages = ["es", "ca", "eu", "gl"]

for config in configs:
    for lang in languages:
        source_filename = f"bannerweb-enviogratis-{source_brand}-2026012q-{lang}.html"
        source_path = os.path.join(base_dir, source_filename)
        
        target_filename = f"bannerweb-enviogratis-{config['target_filename_part']}-{lang}.html"
        target_path = os.path.join(base_dir, target_filename)
        
        if not os.path.exists(source_path):
            print(f"Error: Source file not found: {source_path}")
            continue
            
        with open(source_path, 'r') as f:
            content = f.read()
            
        # Replacements
        
        # 1. Slug (do this early to avoid partial matches)
        content = content.replace(source_slug, config['slug_replacement'])
        
        # 2. Tracking Code
        content = content.replace(source_tracking, config['tracking_replacement'])
        
        # 3. Image Names (specifically the part we identified)
        content = content.replace(source_image_part, config['image_replacement_part'])
        
        # 4. Price
        if config['price_replacement'] != source_price:
            content = content.replace(source_price, config['price_replacement'])
            
        # 5. Brand Name (Text)
        # Use regex to replace "Heineken" with new Brand, preserving case if possible,
        # but mostly targetting the specific "Heineken" text.
        # Simple replace for "Heineken" -> "Brand"
        content = content.replace("Heineken", config['brand_display'])
        content = content.replace("HEINEKEN", config['brand_display'].upper())
        
        # 6. Sanity check: replace remaining 'heineken' in lower case if it's not part of a URL/path we already handled?
        # Maybe dangerous. But let's check filenames in links?
        # The slug replacement should cover the main link.
        # We might have `alt="Heineken"` which is covered by step 5.
        
        with open(target_path, 'w') as f:
            f.write(content)
            
        print(f"Created: {target_filename}")

