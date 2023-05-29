#!/bin/sh

rm -rf ./packaging && mkdir ./packaging
mkdir ./packaging/post-to-discord
cp -r includes/ packaging/post-to-discord/includes/
cp -r views/ packaging/post-to-discord/views/
cp post-to-discord.php packaging/post-to-discord/
cp LICENSE packaging/post-to-discord/
cp readme.txt packaging/post-to-discord/
cd packaging
zip -r post-to-discord.zip .
