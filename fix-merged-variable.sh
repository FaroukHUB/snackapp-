#!/bin/bash
# Fix: Renommer $merged en $mergedSupIds à la ligne 298 de config.php
sed -i 's/\$merged = array_unique(array_merge(\$existingIds, \$runtimeSupIds));/\$mergedSupIds = array_unique(array_merge(\$existingIds, \$runtimeSupIds));/' ~/Marvelous.mon-agenceweb.fr/admin-panel-v2/config.php
sed -i 's/\$supplements\['\''defaultForCategories'\''\]\[\$catId\] = array_values(\$merged);/\$supplements['\''defaultForCategories'\''][\$catId] = array_values(\$mergedSupIds);/' ~/Marvelous.mon-agenceweb.fr/admin-panel-v2/config.php
echo "✅ Fix appliqué!"
