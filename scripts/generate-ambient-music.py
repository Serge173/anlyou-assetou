#!/usr/bin/env python3
"""
Génère une musique piano romantique de mariage (libre de droits) pour le faire-part.
Sortie : public/assets/audio/ambient.mp3
"""
from __future__ import annotations

import math
import struct
from pathlib import Path

import numpy as np

try:
    import lameenc
except ImportError as exc:
    raise SystemExit("Installez lameenc : pip install lameenc numpy") from exc

SAMPLE_RATE = 44100
DURATION = 56.0
OUTPUT = Path(__file__).resolve().parent.parent / "public" / "assets" / "audio" / "ambient.mp3"


def midi_to_hz(note: float) -> float:
    return 440.0 * (2.0 ** ((note - 69) / 12.0))


def piano_tone(freq: float, duration: float, velocity: float = 0.35) -> np.ndarray:
    n = int(duration * SAMPLE_RATE)
    if n <= 0:
        return np.array([], dtype=np.float64)
    t = np.arange(n, dtype=np.float64) / SAMPLE_RATE
    tone = np.zeros(n, dtype=np.float64)
    partials = (
        (1.0, 1.00, 2.0),
        (2.0, 0.42, 3.5),
        (3.0, 0.20, 5.0),
        (4.0, 0.10, 6.5),
        (5.0, 0.05, 8.5),
    )
    for harmonic, amp, decay in partials:
        tone += amp * np.sin(2 * math.pi * freq * harmonic * t) * np.exp(-decay * t)
    attack = min(int(0.018 * SAMPLE_RATE), n)
    env = np.ones(n)
    if attack:
        env[:attack] = np.linspace(0.0, 1.0, attack)
    tone *= env * velocity
    return tone


def mix_at(buffer: np.ndarray, offset: int, clip: np.ndarray) -> None:
    end = offset + len(clip)
    if offset >= len(buffer):
        return
    if end > len(buffer):
        clip = clip[: len(buffer) - offset]
        end = len(buffer)
    buffer[offset:end] += clip


def soft_reverb(signal: np.ndarray, delay_ms: float = 45.0, mix: float = 0.22) -> np.ndarray:
    delay = int(SAMPLE_RATE * delay_ms / 1000.0)
    wet = np.zeros_like(signal)
    wet[delay:] = signal[:-delay] * 0.58
    wet[delay * 2 :] += signal[: -delay * 2] * 0.30
    wet[delay * 3 :] += signal[: -delay * 3] * 0.14
    return signal * (1 - mix) + wet * mix


def fade_edges(signal: np.ndarray, fade_s: float = 3.0) -> np.ndarray:
    n = len(signal)
    fade = int(fade_s * SAMPLE_RATE)
    fade = min(fade, n // 4)
    out = signal.copy()
    ramp = np.linspace(0.0, 1.0, fade)
    out[:fade] *= ramp
    out[-fade:] *= ramp[::-1]
    return out


def build_track() -> np.ndarray:
    total = int(DURATION * SAMPLE_RATE)
    track = np.zeros(total, dtype=np.float64)

    # Valse de mariage en 3/4 : C — Am — F — G
    chords = [
        [48, 52, 55, 60],
        [45, 52, 57, 60],
        [41, 48, 53, 57],
        [43, 47, 50, 55],
    ]
    measure = 2.4
    waltz_pattern = [0, 1, 2, 1]

    for bar, chord in enumerate(chords * 4):
        bar_start = int(bar * measure * SAMPLE_RATE)
        beat = measure / 3
        for beat_idx, note_idx in enumerate(waltz_pattern):
            start = bar_start + int(beat_idx * beat * SAMPLE_RATE)
            midi = chord[note_idx % len(chord)]
            vel = 0.26 if beat_idx == 0 else 0.16
            note_len = beat * (1.15 if beat_idx == 0 else 0.85)
            clip = piano_tone(midi_to_hz(midi), note_len, vel)
            mix_at(track, start, clip)

        for midi in chord[:3]:
            freq = midi_to_hz(midi - 12)
            clip = piano_tone(freq, measure * 0.92, 0.05)
            mix_at(track, bar_start, clip)

    melody = [72, 76, 79, 76, 74, 72, 71, 72, 74, 76, 79, 81, 79, 76, 74, 72]
    mel_step = measure / 2
    for i, midi in enumerate(melody * 3):
        start = int(i * mel_step * SAMPLE_RATE)
        clip = piano_tone(midi_to_hz(midi), mel_step * 0.95, 0.13)
        mix_at(track, start, clip)

    track = soft_reverb(track)
    track = fade_edges(track)

    peak = np.max(np.abs(track)) or 1.0
    track = track / peak * 0.80
    return track


def to_mp3(pcm: np.ndarray, path: Path) -> None:
    pcm16 = np.clip(pcm * 32767.0, -32768, 32767).astype(np.int16)
    encoder = lameenc.Encoder()
    encoder.set_bit_rate(128)
    encoder.set_in_sample_rate(SAMPLE_RATE)
    encoder.set_channels(1)
    encoder.set_quality(2)
    mp3_data = encoder.encode(pcm16.tobytes())
    mp3_data += encoder.flush()
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(mp3_data)


def main() -> None:
    print("Génération de la musique piano de mariage…")
    pcm = build_track()
    to_mp3(pcm, OUTPUT)
    size_kb = OUTPUT.stat().st_size / 1024
    print(f"Fichier créé : {OUTPUT} ({size_kb:.1f} Ko)")


if __name__ == "__main__":
    main()
