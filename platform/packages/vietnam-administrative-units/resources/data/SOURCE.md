# Dataset provenance

- Legal source: Decision 19/2025/QD-TTg dated 30 June 2025.
- Effective date: 1 July 2025.
- Issuer: Prime Minister of Vietnam.
- Official document page: https://vanban.chinhphu.vn/?classid=1&docid=214409&pageid=27160
- Official gazette: https://congbao.chinhphu.vn/van-ban/quyet-dinh-so-19-2025-qd-ttg-45430/57441.htm

The checked-in CSV files are transcribed from Appendices I and II of the
official gazette. Dataset invariants enforced by code and tests:

- 34 unique two-digit province-level codes.
- 3,321 unique five-digit commune-level codes.
- Every commune-level record references one published province-level code.

When the Government publishes a replacement list, update both CSV files,
the effective date/count constants, tests, and this provenance note. Run the
sync command without `--apply` first and review its summary before writing.
