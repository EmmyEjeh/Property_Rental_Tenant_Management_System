# Local visual evidence service

Runs on the same Windows computer as XAMPP. It accepts two images, compares grayscale pixels with OpenCV, and returns a visual-change report. It does **not** use a cloud API or send images over the internet.

This implementation is deliberately described as local computer-vision change detection, not a trained AI damage classifier. It cannot decide whether a difference is damage, ordinary wear, tenant-caused, or chargeable. A manager must inspect evidence and enter the final deduction.

Install in a Python virtual environment and start with `python -m uvicorn main:app --host 127.0.0.1 --port 8001`. Keep the host bound to `127.0.0.1`.
