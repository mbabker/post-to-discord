#!/bin/sh

if ! [[ -d vendor ]]; then
	echo "The vendor folder was not found. Did you run 'composer install' before packaging?"

	exit 1
fi

rm -rf ./packaging && mkdir ./packaging
mkdir ./packaging/post-to-discord
cp -r includes/ packaging/post-to-discord/includes/
cp -r src/ packaging/post-to-discord/src/
cp -r vendor/ packaging/post-to-discord/vendor/
cp -r views/ packaging/post-to-discord/views/
cp post-to-discord.php packaging/post-to-discord/
cp LICENSE packaging/post-to-discord/
cp readme.txt packaging/post-to-discord/
cd packaging
zip -r post-to-discord.zip .
