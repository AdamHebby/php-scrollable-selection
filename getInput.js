const readline = require('readline');
readline.emitKeypressEvents(process.stdin);
process.stdin.setRawMode(true);

process.stdin.on('keypress', (str, key) => {
    if (key.sequence === '\r' || key.name === 'return' || key.name === 'enter') {
        console.log('return');
        process.stdin.setRawMode(false);
        process.exit();
    }

    if (key !== undefined) {
        if (key.name !== undefined) {
            console.log(key.name);
        } else if (key.sequence !== undefined) {
            console.log(key.sequence);
        }
    }

    if (key.sequence === '\u0003' || key.sequence === '\u001b') {
        process.stdin.setRawMode(false);
        process.exit();
    }
});