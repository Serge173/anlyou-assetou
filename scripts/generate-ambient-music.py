#!/usr/bin/env python3
"""
Génère une musique piano ambiante originale (libre de droits) pour l'intro du faire-part.
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
DURATION = 48.0  # boucle fluide
OUTPUT = Path(__file__).resolve().parent.parent / "public" / "assets" / "audio" / "ambient.mp3"

# Notes MIDI -> fréquence
def midi_to_hz(note: float) -> float:
    return 440.0 * (2.0 ** ((note - 69) / 12.0))


def piano_tone(freq: float, duration: float, velocity: float = 0.35) -> np.ndarray:
    """Synthèse additive type piano avec décroissance par harmonique."""
    n = int(duration * SAMPLE_RATE)
    if n <= 0:
        return np.array([], dtype=np.float64)
    t = np.arange(n, dtype=np.float64) / SAMPLE_RATE
    tone = np.zeros(n, dtype=np.float64)
    partials = (
        (1.0, 1.00, 2.2),
        (2.0, 0.45, 3.8),
        (3.0, 0.22, 5.5),
        (4.0, 0.12, 7.0),
        (5.0, 0.06, 9.0),
        (6.0, 0.03, 11.0),
    )
    for harmonic, amp, decay in partials:
        tone += amp * np.sin(2 * math.pi * freq * harmonic * t) * np.exp(-decay * t)
    attack = min(int(0.012 * SAMPLE_RATE), n)
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


def soft_reverb(signal: np.ndarray, delay_ms: float = 38.0, mix: float = 0.18) -> np.ndarray:
    delay = int(SAMPLE_RATE * delay_ms / 1000.0)
    wet = np.zeros_like(signal)
    wet[delay:] = signal[:-delay] * 0.55
    wet[delay * 2 :] += signal[: -delay * 2] * 0.28
    return signal * (1 - mix) + wet * mix


def fade_edges(signal: np.ndarray, fade_s: float = 2.5) -> np.ndarray:
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

    # Progression romantique : Am7 — F — C — G (arpèges doux)
    chords = [
        [57, 60, 64, 67],   # A3 C4 E4 G4
        [53, 57, 60, 65],   # F3 A3 C4 F4
        [48, 52, 55, 60],   # C3 E3 G3 C4
        [55, 59, 62, 67],   # G3 B3 D4 G4
    ]
    beat = 1.85  # secondes par mesure
    arp_notes_per_chord = 8

    for bar, chord in enumerate(chords * 3):  # 12 mesures
        bar_start = int(bar * beat * SAMPLE_RATE)
        step = beat / arp_notes_per_chord
        for i, midi in enumerate(
            [chord[i % len(chord)] for i in range(arp_notes_per_chord)]
        ):
            start = bar_start + int(i * step * SAMPLE_RATE)
            freq = midi_to_hz(midi)
            vel = 0.22 if midi < 55 else 0.18
            note_len = step * 1.35
            clip = piano_tone(freq, note_len, vel)
            mix_at(track, start, clip)

    # Accords tenus très légers en fond
    for bar, chord in enumerate(chords * 3):
        bar_start = int(bar * beat * SAMPLE_RATE)
        for midi in chord[:3]:
            freq = midi_to_hz(midi - 12)
            clip = piano_tone(freq, beat * 0.95, 0.06)
            mix_at(track, bar_start, clip)

    # Mélodie simple au-dessus (do — mi — sol — la)
    melody = [72, 74, 76, 74, 72, 71, 72, 74]
    mel_step = beat / 2
    for i, midi in enumerate(melody * 6):
        start = int(i * mel_step * SAMPLE_RATE)
        clip = piano_tone(midi_to_hz(midi), mel_step * 1.1, 0.14)
        mix_at(track, start, clip)

    track = soft_reverb(track)
    track = fade_edges(track)

    peak = np.max(np.abs(track)) or 1.0
    track = track / peak * 0.82
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
    print("Génération de la musique piano ambiante…")
    pcm = build_track()
    to_mp3(pcm, OUTPUT)
    size_kb = OUTPUT.stat().st_size / 1024
    print(f"Fichier créé : {OUTPUT} ({size_kb:.1f} Ko)")


if __name__ == "__main__":
    main()
