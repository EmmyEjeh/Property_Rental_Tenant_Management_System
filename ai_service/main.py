"""Local, privacy-preserving visual-change analysis for tenancy evidence.
This is image-difference computer vision, not a trained damage classifier.
"""
from fastapi import FastAPI, File, UploadFile, HTTPException
import cv2
import numpy as np

app = FastAPI(title="Local Deposit Evidence Vision", version="1.0.0")
MAX_BYTES = 12 * 1024 * 1024

async def read_image(upload: UploadFile):
    raw = await upload.read(MAX_BYTES + 1)
    if len(raw) > MAX_BYTES:
        raise HTTPException(413, "Each image must be 12 MB or smaller.")
    arr = np.frombuffer(raw, dtype=np.uint8)
    image = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    if image is None:
        raise HTTPException(400, "The uploaded file is not a readable image.")
    h, w = image.shape[:2]
    if w < 100 or h < 100:
        raise HTTPException(400, "Images must be at least 100 by 100 pixels.")
    return image

@app.get("/health")
def health():
    return {"status": "ready", "engine": "OpenCV visual-difference analysis", "model_loaded": True,
            "classification_warning": "This service detects visual changes only; it does not determine damage, cause, liability or repair cost."}

@app.post("/compare")
async def compare(move_in: UploadFile = File(...), move_out: UploadFile = File(...)):
    a = await read_image(move_in)
    b = await read_image(move_out)
    # Resize to a common comparison canvas. Pair similar viewpoints for best results.
    target_w = min(900, max(320, min(a.shape[1], b.shape[1])))
    target_h = min(900, max(320, min(a.shape[0], b.shape[0])))
    a = cv2.resize(a, (target_w, target_h), interpolation=cv2.INTER_AREA)
    b = cv2.resize(b, (target_w, target_h), interpolation=cv2.INTER_AREA)
    ga = cv2.cvtColor(a, cv2.COLOR_BGR2GRAY)
    gb = cv2.cvtColor(b, cv2.COLOR_BGR2GRAY)
    # Blur suppresses small sensor/compression noise; threshold marks substantial pixel changes.
    ga = cv2.GaussianBlur(ga, (5, 5), 0)
    gb = cv2.GaussianBlur(gb, (5, 5), 0)
    diff = cv2.absdiff(ga, gb)
    _, mask = cv2.threshold(diff, 28, 255, cv2.THRESH_BINARY)
    changed = float(np.count_nonzero(mask)) / float(mask.size) * 100.0
    mean_diff = float(np.mean(diff))
    contours, _ = cv2.findContours(mask, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    areas = sorted([int(cv2.contourArea(c)) for c in contours if cv2.contourArea(c) >= 150], reverse=True)[:10]
    if changed < 2:
        band = "low_visual_difference"
        summary = "Few substantial pixel changes were detected. This does not prove the room is undamaged."
    elif changed < 12:
        band = "moderate_visual_difference"
        summary = "Some visual changes were detected. Review the highlighted areas and original photos manually."
    else:
        band = "high_visual_difference"
        summary = "Many visual changes were detected. Check for viewpoint, lighting, furniture and occupancy differences before deciding whether damage occurred."
    return {"engine": "OpenCV visual-difference analysis", "width": target_w, "height": target_h,
            "changed_pixel_percent": round(changed, 2), "mean_pixel_difference": round(mean_diff, 2),
            "change_band": band, "summary": summary, "largest_changed_regions_px": areas,
            "limitations": ["This is not a trained damage-recognition model.", "Different camera angles, lighting, shadows and furniture can create false differences.", "The system cannot determine cause, tenant responsibility, ordinary wear and tear, legal liability or repair cost."],
            "manager_review_required": True}
