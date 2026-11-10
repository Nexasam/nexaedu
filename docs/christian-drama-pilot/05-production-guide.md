# The Alert: Production Guide

This guide takes the finished script and reference pack through production steps 5-10.

## 5. Produce the First Version in Flow

### Project setup

1. Create one project named `The Alert - Pilot v1`.
2. Upload the three files in `reference-assets/` as character and location ingredients.
3. Generate the empty-room wide shot first. Use it as the environmental reference for later shots.
4. Generate Daniel-only and Femi-only neutral medium shots before attempting two-shots.
5. Work in story order, but approve each shot for face, wardrobe, room geography, light direction, and hand quality before continuing.
6. Generate two variations per shot initially. Only create more when a specific defect has been identified.

### Prompt structure

Use this order for every request:

```text
[Shot size and camera position].
[Character identity and exact action].
[Emotion expressed through one small physical behavior].
[Location, time, and light direction].
[Camera movement and duration].
[Shared prompt base and exclusions].
```

Example for shot 19:

```text
Static 85mm close-up of Daniel Adebayo, matching the supplied Daniel reference exactly.
He looks toward Femi just off camera and reaches a quiet moral decision; his jaw relaxes
and his eyes become steady, without smiling or preaching toward camera. Exact apartment
background and late-afternoon window light from camera-left. Six-second take, locked camera,
natural blinking and breathing. Grounded Nigerian drama, photorealistic cinema, no speech,
no face drift, no wardrobe change, no text, no extra people, no exaggerated acting.
```

### Acceptance checklist

A shot is approved only when:

- the face matches its reference throughout the usable section;
- wardrobe, wristwatch, bracelet, and props remain consistent;
- the window and door remain on the correct sides;
- fingers, phone, and furniture are anatomically and spatially credible;
- camera motion supports the emotion instead of advertising the generation tool;
- at least two clean seconds exist before and after the main action.

Track every approved clip in a simple log: shot number, tool, prompt version, take number, duration, defect notes, and approved filename.

## 6. Use Seedance Only for Difficult Shots

Do not regenerate the entire film in a second model. Send a shot to Seedance only after two focused Flow attempts fail for the same reason.

Best candidates are subtle emotional close-ups such as shots 4, 19, or 20. Hands, phone screens, notices, and food should be filmed practically where possible because that is faster and more controllable than model switching.

For each Seedance attempt:

1. Supply the same reference image and the nearest approved frame from the preceding shot.
2. Request one action and one camera move only.
3. Generate without dialogue or embedded text.
4. Match the approved room grade in Resolve rather than asking the model for many color revisions.
5. Record the model and prompt in the shot log for reproducibility.

## 7. Record Dialogue and Sound

Real actors are the preferred route for this pilot. Their voices can carry emotional continuity even when the images are generated.

### Recording

- Record each actor separately in the same quiet, soft-furnished room.
- Use a lavalier or cardioid microphone 15-20 cm from the mouth.
- Record 48 kHz, 24-bit WAV with peaks around -12 dBFS.
- Capture three full performances, then individual pickup lines.
- Record 60 seconds of room tone and breaths/reactions for both characters.
- Direct for restrained conversation, not pulpit delivery.

If a synthetic or cloned voice is used, obtain written permission covering the film, trailers, monetisation, duration, and revocation terms. Keep the consent with the project files. Do not clone a pastor, actor, or public figure without explicit consent.

### Sound layers

Use dialogue, room tone, distant Lagos traffic, fan noise, phone vibrations, cloth movement, and food-wrapper Foley. Music should enter only when the moral pressure deepens and should not manufacture a miraculous ending.

## 8. Finish in DaVinci Resolve

### Timeline and folders

- Timeline: 1920x1080, 24 fps, Rec.709 Gamma 2.4.
- Bins: `01 Video`, `02 Dialogue`, `03 Foley`, `04 Music`, `05 Phone Comps`, `06 Graphics`, `07 Exports`.
- Rename clips by shot and take, for example `S19_T03_APPROVED`.
- Build the dialogue edit first, then fit pictures to it.

### Edit order

1. Assemble the full three-minute dialogue and pauses.
2. Add wide shots and clean reactions; hide mouth mismatches with inserts and over-shoulders.
3. Build and track the four phone graphics in Fusion.
4. Add room tone and Foley before music.
5. Match exposure, skin tones, contrast, and window warmth across all clips.
6. Add restrained captions and the final scripture card.
7. Mix dialogue around -14 LUFS integrated for web delivery, with peaks below -1 dBTP.

### Exports

- Review master: H.264, 1080p, high quality, stereo AAC.
- Archive master: ProRes 422 or DNxHR HQ with 24-bit WAV.
- Vertical version: 1080x1920, reframed shot-by-shot rather than automatically cropped.
- Caption file: SRT, reviewed manually for Nigerian names and punctuation.

## 9. Run a Private Screening

Screen the film without explaining its message first. Use 8-12 viewers:

- two pastors or mature Christian leaders;
- two Christian creatives or filmmakers;
- four target viewers aged roughly 18-45;
- one or two viewers who are not regular churchgoers.

Collect responses privately before group discussion so confident voices do not shape everybody else's answers.

Ask:

1. In one sentence, what happened in the film?
2. What choice did Daniel make, and why?
3. Did any moment imply that honesty guarantees immediate financial reward?
4. Did either character feel fake, preachy, or unfairly portrayed?
5. Which visual moment looked artificial or broke immersion?
6. What did you feel at the end?
7. Would you share it? Why or why not?
8. Is any line theologically misleading or pastorally unwise?

## 10. Measure and Decide

Use a five-point scale for story clarity, emotional impact, theological confidence, performance believability, visual consistency, and sound quality.

The pilot is ready to publish when:

- at least 80% independently identify integrity as the central choice;
- no reviewer identifies a serious theological error;
- average story clarity and emotional impact are at least 4/5;
- average visual consistency is at least 3.5/5;
- fewer than 20% misunderstand who sent the money or why Daniel did not transfer it directly;
- no unlicensed voice, music, image, logo, or scripture translation remains;
- two reviewers watch the final export on a phone and can read every caption.

Revise the story before polishing visuals if clarity fails. Revise dialogue or performance if the theology feels like a sermon. Revise individual shots only when the story works but immersion breaks.

## Recommended First Production Day

1. Record a table read and time the screenplay.
2. Adjust dialogue until the read lands between 2:35 and 2:45, leaving room for silence and the end card.
3. Film the practical phone, hand, notice, Bible, and food inserts with any modern smartphone.
4. Generate shots 1, 4, 5, 8, 10, and 19 as the visual-consistency test.
5. Assemble those clips with temporary dialogue before spending credits on the remaining shots.

That short test answers the most important question early: whether Daniel, Femi, and the apartment remain convincing enough to sustain the complete film.
