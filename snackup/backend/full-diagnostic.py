#!/usr/bin/env python3
"""
Script de diagnostic complet - Compare backup vs menu.json actuel
"""

import json

print("🔍 DIAGNOSTIC COMPLET - COMPARAISON MENU.JSON")
print("=" * 60)
print()

# Charger les deux fichiers
with open('menu.json.backup-20260105-134315', 'r', encoding='utf-8') as f:
    backup = json.load(f)

with open('menu.json', 'r', encoding='utf-8') as f:
    current = json.load(f)

# 1. UpsellRules
print("📌 1. UPSELL RULES")
backup_upsells = backup.get('upsellRules', [])
current_upsells = current.get('upsellRules', [])
print(f"   Backup:  {len(backup_upsells)} règles")
print(f"   Actuel:  {len(current_upsells)} règles")
if len(backup_upsells) == len(current_upsells):
    print("   ✅ OK - Nombre identique")
else:
    print(f"   ❌ PERDU: {len(backup_upsells) - len(current_upsells)} règles")
print()

# 2. Formules
print("📌 2. FORMULES")
backup_formules = backup.get('formules', [])
current_formules = current.get('formules', [])
print(f"   Backup:  {len(backup_formules)} formules")
print(f"   Actuel:  {len(current_formules)} formules")
if len(backup_formules) == len(current_formules):
    print("   ✅ OK" if len(backup_formules) > 0 else "   ⚠️  Aucune formule (peut être normal)")
else:
    print(f"   ❌ PERDU: {len(backup_formules) - len(current_formules)} formules")
    if len(backup_formules) > 0:
        print(f"   Formules perdues:")
        for f in backup_formules:
            print(f"      - {f.get('name', 'sans nom')} ({f.get('price', 0)} DA)")
print()

# 3. Options spéciales (pâtisserie, beverages)
print("📌 3. OPTIONS SPÉCIALES (pâtisserieOptions, beverageOptions)")

def count_special_options(data):
    patisserie_count = 0
    beverage_count = 0

    for cat in data.get('menu', {}).get('categories', []):
        for item in cat.get('items', []):
            if 'pâtisserieOptions' in item:
                patisserie_count += len(item['pâtisserieOptions'])
            if 'beverageOptions' in item:
                beverage_count += len(item['beverageOptions'])

    return patisserie_count, beverage_count

backup_pat, backup_bev = count_special_options(backup)
current_pat, current_bev = count_special_options(current)

print(f"   Backup:  {backup_pat} pâtisseries, {backup_bev} boissons")
print(f"   Actuel:  {current_pat} pâtisseries, {current_bev} boissons")

if backup_pat > 0 and current_pat == 0:
    print(f"   ❌ PERDU: {backup_pat} options pâtisserie")
if backup_bev > 0 and current_bev == 0:
    print(f"   ❌ PERDU: {backup_bev} options boissons")
print()

# 4. Prix manquants (prix = 0)
print("📌 4. PRODUITS AVEC PRIX = 0")

def find_zero_prices(data):
    zero_price_products = []
    for cat in data.get('menu', {}).get('categories', []):
        for item in cat.get('items', []):
            if item.get('priceSolo', 0) == 0:
                zero_price_products.append({
                    'name': item.get('name', 'sans nom'),
                    'category': cat.get('name', 'sans catégorie')
                })
    return zero_price_products

backup_zeros = find_zero_prices(backup)
current_zeros = find_zero_prices(current)

print(f"   Backup:  {len(backup_zeros)} produits avec prix=0")
print(f"   Actuel:  {len(current_zeros)} produits avec prix=0")

if len(current_zeros) > len(backup_zeros):
    print(f"   ❌ PROBLÈME: {len(current_zeros) - len(backup_zeros)} produits ont PERDU leur prix")
    print(f"   Produits affectés:")
    for p in current_zeros[:10]:  # Afficher les 10 premiers
        print(f"      - {p['name']} ({p['category']})")
    if len(current_zeros) > 10:
        print(f"      ... et {len(current_zeros) - 10} autres")
print()

# 5. Nombre total de catégories et produits
print("📌 5. CATÉGORIES ET PRODUITS")
backup_cats = backup.get('menu', {}).get('categories', [])
current_cats = current.get('menu', {}).get('categories', [])

backup_products = sum(len(cat.get('items', [])) for cat in backup_cats)
current_products = sum(len(cat.get('items', [])) for cat in current_cats)

print(f"   Catégories: {len(backup_cats)} (backup) → {len(current_cats)} (actuel)")
print(f"   Produits:   {backup_products} (backup) → {current_products} (actuel)")
print()

# 6. Suppléments
print("📌 6. SUPPLÉMENTS")
backup_supps = backup.get('supplements', {}).get('catalog', {})
current_supps = current.get('supplements', {}).get('catalog', {})
print(f"   Backup:  {len(backup_supps)} suppléments")
print(f"   Actuel:  {len(current_supps)} suppléments")
print()

# Résumé
print("=" * 60)
print("📊 RÉSUMÉ")
print("=" * 60)

issues = []
if len(current_formules) < len(backup_formules):
    issues.append(f"❌ {len(backup_formules) - len(current_formules)} formules perdues")
if backup_pat > 0 and current_pat == 0:
    issues.append(f"❌ {backup_pat} options pâtisserie perdues")
if backup_bev > 0 and current_bev == 0:
    issues.append(f"❌ {backup_bev} options boissons perdues")
if len(current_zeros) > len(backup_zeros):
    issues.append(f"❌ {len(current_zeros) - len(backup_zeros)} produits ont perdu leur prix")

if issues:
    print("⚠️  PROBLÈMES DÉTECTÉS:")
    for issue in issues:
        print(f"   {issue}")
    print()
    print("🔧 SOLUTION: Restaurer menu.json depuis le backup du 5 janvier")
    print("   → bash database/restore-menu.sh")
else:
    print("✅ Aucun problème majeur détecté")
