#!/bin/sh

trap "exit 1" USR1
PROC="$$"

fatal(){
	echo "$@" >&2
	kill -10 $PROC
}

# Exclude bin/ here because it contains PHP scripts. However, we check bin/
# in the block below just in case it has anything with a #!/bin/sh shebang.
# Although this isn't a perfect check, the likelihood of a shell script with
# the "wrong" shebang being placed in bin/ is very small.
grep -rl '\(^#!\/bin\/.\+sh\)\|\(^#!\/usr\/bin\)' . --exclude=vendor.phar --exclude-dir=bin --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=local --exclude-dir=.git | while IFS= read -r file
do
	fatal "Error in ${file}: please only use #!/bin/sh shebang"
done

grep -rl '^#!\/bin\/sh' . --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=local --exclude-dir=.git | while IFS= read -r file
do
	echo "Checking ${file}"
	if ! shellcheck "$file"; then
		fatal "Shellcheck error in ${file}"
		exit 1
	fi
done

echo OK
