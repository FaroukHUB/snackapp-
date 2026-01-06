#!/usr/bin/env python3
"""
Script de correction du menu.json - Ajoute priceSolo=0 aux produits sans prix
"""

import json
import os
from datetime import datetime

menu_path = '/home/user/snackapp-/config/menu.json'

# Backup avant modification
backup_path = f"{menu_path}.backup-{datetime.now().strftime('%Y%m%d-%H%M%S')}"
os.system(f"cp {menu_path} {backup_path}")
print(f"✅ Backup créé: {backup_path}")

# Charger menu.json
with open(menu_path, 'r', encoding='utf-8') as f:
    menu_data = json.load(f)

# Corriger les produits sans prix
fixed_count = 0
for category in menu_data.get('menu', {}).get('categories', []):
    for item in category.get('items', []):
        # Si le produit n'a pas de priceSolo, lui donner 0
        if 'priceSolo' not in item or item['priceSolo'] is None:
            item['priceSolo'] = 0
            fixed_count += 1
            print(f"✅ Corrigé: {item.get('name', 'sans nom')} → priceSolo = 0")

# Sauvegarder
with open(menu_path, 'w', encoding='utf-8') as f:
    json.dump(menu_data, f, ensure_ascii=False, indent=2)

print(f"\n✅ {fixed_count} produits corrigés")
print(f"✅ menu.json sauvegardé")
