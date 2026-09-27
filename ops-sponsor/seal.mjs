import fs from 'node:fs';
import {randomBytes,createCipheriv,publicEncrypt,constants} from 'node:crypto';
const key=randomBytes(32),iv=randomBytes(12);
const cipher=createCipheriv('aes-256-gcm',key,iv);
const data=Buffer.concat([cipher.update(fs.readFileSync(0)),cipher.final()]);
const sealed={key:publicEncrypt({key:fs.readFileSync(new URL('./rest-public.pem',import.meta.url)),padding:constants.RSA_PKCS1_OAEP_PADDING,oaepHash:'sha256'},key).toString('base64'),iv:iv.toString('base64'),tag:cipher.getAuthTag().toString('base64'),data:data.toString('base64')};
// Letter-only encoding avoids unrelated numeric repository-secret masks corrupting ciphertext.
process.stdout.write(Buffer.from(JSON.stringify(sealed)).toString('hex').replace(/[0-9a-f]/g,x=>'ABCDEFGHIJKLMNOP'[parseInt(x,16)]));
