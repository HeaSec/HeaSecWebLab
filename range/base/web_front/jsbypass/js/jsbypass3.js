//看不懂对吧，拿去问AI啊^-^

var _0x1ce3=['getElementById','value','submit','ILoveWebSecurityAndHeaSecNB','密码错误','level3Form','password','key'];(function(_0x9c7e1c,_0x1ce31a){var _0x4edf7f=function(_0x1ad425){while(--_0x1ad425){_0x9c7e1c['push'](_0x9c7e1c['shift']());}};_0x4edf7f(++_0x1ce31a);}(_0x1ce3,0x8d));var _0x4edf=function(_0x9c7e1c,_0x1ce31a){_0x9c7e1c=_0x9c7e1c-0x0;var _0x4edf7f=_0x1ce3[_0x9c7e1c];return _0x4edf7f;};function checkPasswordAndSubmit(){var _0x49c24c=document[_0x4edf('0x3')](_0x4edf('0x2'))[_0x4edf('0x4')];const _0x5018ec=_0x4edf('0x6');var _0x15a608=vigenereEncryptSimple(_0x5018ec,_0x49c24c);var _0x28fb99=document[_0x4edf('0x3')](_0x4edf('0x1'))[_0x4edf('0x4')];if(_0x15a608===_0x28fb99){document[_0x4edf('0x3')](_0x4edf('0x0'))[_0x4edf('0x5')]();}else{alert(_0x4edf('0x7'));}}

function vigenereEncryptSimple(plaintext, key) {
    const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charsetLength = 62;
    let encrypted = '';
    const keyLength = key.length;
    for (let i = 0; i < plaintext.length; i++) {
        const plainChar = plaintext[i];
        const plainIndex = charset.indexOf(plainChar);
        const keyChar = key[i % keyLength];
        const keyIndex = charset.indexOf(keyChar);
        const encryptedIndex = (plainIndex + keyIndex) % charsetLength;
        encrypted += charset[encryptedIndex];
    }
    return encrypted;
}
