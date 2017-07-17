#!/bin/bash
CORE_PLG_DIR=~/Projects/besk/php/homestead/Zencart/public

MODULE_DIR=includes/modules/payment/spectrocoin
LANGUAGE_DIR=includes/languages/english/modules/payment

mkdir -p ./$MODULE_DIR ./$LANGUAGE_DIR
cp $CORE_PLG_DIR/spectrocoin_callback.php ./
cp $CORE_PLG_DIR/$MODULE_DIR/../spectrocoin.php ./$MODULE_DIR/..
cp -R $CORE_PLG_DIR/$MODULE_DIR/ ./$MODULE_DIR
cp $CORE_PLG_DIR/$LANGUAGE_DIR/spectrocoin.php ./$LANGUAGE_DIR