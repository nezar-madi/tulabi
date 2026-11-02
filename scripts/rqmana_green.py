"""
rqmana's colours, made green: every blue (OKLCH hue in BLUE_HUES) keeps its perceived
lightness and chroma and only turns to TARGET_HUE ("navy green"), then is brought back into
sRGB by lowering chroma. Status colours (red, amber, green) and other accents are left alone.

    python scripts/rqmana_green.py <source> <destination>   # recolour one file
    python scripts/rqmana_green.py --show                   # print the main brand shades

Used to copy rqmana's design files into tulabi; run it on any file copied from rqmana.
"""
import math
import re
import sys

TARGET_HUE = 160.0
BLUE_HUES = (215.0, 285.0)


def srgb_to_linear(c):
    return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4


def linear_to_srgb(c):
    return 12.92 * c if c <= 0.0031308 else 1.055 * (c ** (1 / 2.4)) - 0.055


def rgb_to_oklch(r, g, b):
    r, g, b = (srgb_to_linear(x / 255) for x in (r, g, b))
    l = 0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b
    m = 0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b
    s = 0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b
    l, m, s = (math.copysign(abs(x) ** (1 / 3), x) for x in (l, m, s))
    L = 0.2104542553 * l + 0.7936177850 * m - 0.0040720468 * s
    a = 1.9779984951 * l - 2.4285922050 * m + 0.4505937099 * s
    bb = 0.0259040371 * l + 0.7827717662 * m - 0.8086757660 * s
    return L, math.hypot(a, bb), math.degrees(math.atan2(bb, a)) % 360


def oklch_to_linear(L, C, h):
    a, b = C * math.cos(math.radians(h)), C * math.sin(math.radians(h))
    l = (L + 0.3963377774 * a + 0.2158037573 * b) ** 3
    m = (L - 0.1055613458 * a - 0.0638541728 * b) ** 3
    s = (L - 0.0894841775 * a - 1.2914855480 * b) ** 3
    return (
        4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
        -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
        -0.0041960863 * l - 0.7034186147 * m + 1.7076147010 * s,
    )


def oklch_to_rgb(L, C, h):
    lo, hi = 0.0, C
    for _ in range(40):  # largest chroma that stays inside sRGB
        mid = (lo + hi) / 2
        if all(-1e-6 <= x <= 1 + 1e-6 for x in oklch_to_linear(L, mid, h)):
            lo = mid
        else:
            hi = mid
    rgb = oklch_to_linear(L, lo, h)
    return tuple(max(0, min(255, round(linear_to_srgb(max(0.0, min(1.0, x))) * 255))) for x in rgb)


def is_blue(r, g, b):
    _, C, h = rgb_to_oklch(r, g, b)
    return C > 0.004 and BLUE_HUES[0] <= h <= BLUE_HUES[1]


def green(r, g, b):
    if not is_blue(r, g, b):
        return (r, g, b)
    L, C, _ = rgb_to_oklch(r, g, b)
    return oklch_to_rgb(L, C, TARGET_HUE)


def recolour(text):
    def hex_sub(match):
        value = match.group(1)
        if len(value) == 3:
            value = ''.join(ch * 2 for ch in value)
        r, g, b = (int(value[i:i + 2], 16) for i in (0, 2, 4))
        nr, ng, nb = green(r, g, b)
        out = f'#{nr:02X}{ng:02X}{nb:02X}'
        return out if match.group(1).isupper() or len(match.group(1)) == 3 else out.lower()

    def triple_sub(match):
        r, g, b = (int(match.group(i)) for i in (2, 3, 4))
        nr, ng, nb = green(r, g, b)
        return f'{match.group(1)}{nr}, {ng}, {nb}'

    text = re.sub(r'#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})(?![0-9a-fA-F])', hex_sub, text)
    # rgb(8, 40, 110) / rgba(8, 40, 110, .2) and bare triples such as "--rq-shadow-color: 8, 40, 110"
    text = re.sub(r'((?:rgba?\(|:\s*))(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?=\s*[,);])', triple_sub, text)
    return text


if __name__ == '__main__':
    if sys.argv[1:] == ['--show']:
        for name, value in [('blue-900', '02163F'), ('blue-800', '08286E'), ('blue-700', '0D4AA8'),
                            ('blue-600', '006DC5'), ('blue-500', '0192F1'), ('blue-300', '7CC4F8'),
                            ('blue-200', 'C7D6EE'), ('blue-100', 'DCE8FA'), ('blue-50', 'EEF4FC'),
                            ('paper', 'F3F7FC'), ('ink-900', '33393F')]:
            r, g, b = (int(value[i:i + 2], 16) for i in (0, 2, 4))
            print(f'{name:9} #{value} -> #{"%02X%02X%02X" % green(r, g, b)}')
    else:
        source, destination = sys.argv[1], sys.argv[2]
        content = open(source, encoding='utf-8').read()
        open(destination, 'w', encoding='utf-8', newline='\n').write(recolour(content))
