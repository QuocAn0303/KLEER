import sys
sys.stdout.reconfigure(encoding='utf-8')
import pandas as pd

df = pd.read_csv('data/woocommerce_500_skus.csv')
steps = ['makeup_remover', 'cleanser', 'toner', 'serum', 'moisturizer', 'sunscreen']
types = ['sensitive', 'dry', 'combination', 'oily', 'normal']
concerns = ['acne', 'dark_spots', 'aging', 'pores']

print("BẢNG 1: Loại da x Bước Routine")
matrix1 = {t: {s: 0 for s in steps} for t in types}
for _, row in df.iterrows():
    s = row['Taxonomy: routine_step']
    t_list = [x.strip() for x in str(row['Taxonomy: skin_type']).split(',') if x.strip()]
    for t in t_list:
        if t in matrix1 and s in matrix1[t]:
            matrix1[t][s] += 1

m1_df = pd.DataFrame(matrix1).T[steps]
print(m1_df)

print("\nBẢNG 2: Vấn đề da x Bước Routine")
matrix2 = {c: {s: 0 for s in steps} for c in concerns}
for _, row in df.iterrows():
    s = row['Taxonomy: routine_step']
    c_val = str(row['Taxonomy: skin_concern']).strip()
    if c_val in matrix2 and s in matrix2[c_val]:
        matrix2[c_val][s] += 1

m2_df = pd.DataFrame(matrix2).T[steps]
print(m2_df)

print("\nBẢNG 3: Cờ an toàn x Bước Routine")
m3 = {'Gentle Fallback (_kleer_gentle_fallback=1)': {}, 'Strong Actives (_kleer_strong_active=1)': {}}
for s in steps:
    sub = df[df['Taxonomy: routine_step'] == s]
    m3['Gentle Fallback (_kleer_gentle_fallback=1)'][s] = int((sub['Meta: _kleer_gentle_fallback'] == 1).sum())
    m3['Strong Actives (_kleer_strong_active=1)'][s] = int((sub['Meta: _kleer_strong_active'] == 1).sum())
m3_df = pd.DataFrame(m3).T[steps]
print(m3_df)
