"""Giai ma doc lap cac PNG QR do test-qr-encoder.php xuat ra va so voi du lieu goc."""
import sys, glob, os, cv2
d = sys.argv[1]; ok = bad = 0
for f in sorted(glob.glob(os.path.join(d, 'qr_*.png')), key=lambda p: int(os.path.basename(p)[3:-4])):
    exp = open(f[:-4] + '.txt', encoding='utf8').read()
    img = cv2.imread(f); img = cv2.copyMakeBorder(img, 20, 20, 20, 20, cv2.BORDER_CONSTANT, value=(255, 255, 255))
    got, _, _ = cv2.QRCodeDetector().detectAndDecode(img)
    good = got == exp; ok += good; bad += (not good)
    print(('PASS ' if good else 'FAIL ') + os.path.basename(f) + ('' if good else f' got={got!r}'))
print(f'{ok} PASS, {bad} FAIL'); sys.exit(1 if bad else 0)
