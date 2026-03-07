const quizData = {
    "Bipolar": {
        "Chapter 1": [
            {
                "q": "What is a bipolar recording?",
                "options": [
                    "Recording with two active electrodes",
                    "Recording with one active electrode",
                    "Recording with no electrodes",
                    "Recording with ground only"
                ],
                "correct": 0,
                "explanation": "Bipolar recording uses two active electrodes to measure the potential difference between them."
            },
            {
                "q": "What is the primary advantage of bipolar recording?",
                "options": [
                    "Measures absolute voltage",
                    "Provides high spatial resolution",
                    "Requires fewer electrodes",
                    "Eliminates all noise"
                ],
                "correct": 1,
                "explanation": "It measures localized activity by distinguishing differences between two closely placed electrodes, improving spatial resolution."
            },
            {
                "q": "How does a differential amplifier work in a bipolar setup?",
                "options": [
                    "Adds the signals",
                    "Subtracts the signals",
                    "Multiplies the signals",
                    "Ignores the signals"
                ],
                "correct": 1,
                "explanation": "A differential amplifier amplifies the difference between the two active electrodes, effectively subtracting one from the other."
            },
            {
                "q": "What happens to common-mode noise in a bipolar recording?",
                "options": [
                    "It is amplified",
                    "It is rejected",
                    "It is inverted",
                    "It becomes the primary signal"
                ],
                "correct": 1,
                "explanation": "Signals that are identical (common) at both electrodes are rejected by the differential amplifier."
            },
            {
                "q": "Why is bipolar recording often preferred for EMG?",
                "options": [
                    "It captures distant muscle activity",
                    "It localizes specific motor unit activity",
                    "It is easier to attach",
                    "It avoids the need for a ground"
                ],
                "correct": 1,
                "explanation": "Bipolar EMG focuses on localized muscle activity while rejecting cross-talk from distant muscles."
            },
            {
                "q": "In a bipolar setup, what happens if both electrodes pick up the exact same signal?",
                "options": [
                    "The output is doubled",
                    "The output is zero",
                    "The signal is distorted",
                    "The amplifier clips"
                ],
                "correct": 1,
                "explanation": "Because the amplifier measures the difference, identical signals result in a zero output."
            },
            {
                "q": "What is the typical inter-electrode spacing for bipolar EMG?",
                "options": [
                    "1-2 cm",
                    "5-10 cm",
                    "15-20 cm",
                    "It doesn't matter"
                ],
                "correct": 0,
                "explanation": "A 1-2 cm spacing is typically used to optimize spatial resolution without excessive signal cancellation."
            },
            {
                "q": "Does a bipolar recording measure absolute voltage?",
                "options": [
                    "Yes",
                    "always",
                    "No",
                    "it measures a potential difference",
                    "Only if the ground is attached",
                    "Only in EEG"
                ],
                "correct": 1,
                "explanation": "It only provides the relative difference between the two active electrode sites."
            },
            {
                "q": "What is a common clinical application of bipolar recording?",
                "options": [
                    "Routine 12-lead ECG",
                    "Routine EEG",
                    "Conduction velocity studies",
                    "All of the above"
                ],
                "correct": 2,
                "explanation": "Nerve conduction velocity studies frequently use bipolar setups to precise locate action potentials."
            },
            {
                "q": "What is 'cross-talk' in the context of EMG?",
                "options": [
                    "Talking during the exam",
                    "Interference from power lines",
                    "Signal detected from an adjacent muscle",
                    "Faulty wiring"
                ],
                "correct": 2,
                "explanation": "Cross-talk is the detection of electrical activity from muscles other than the target muscle."
            }
        ],
        "Chapter 2": [
            {
                "q": "How does electrode spacing affect bipolar signal amplitude?",
                "options": [
                    "Closer spacing increases amplitude",
                    "Wider spacing increases amplitude",
                    "Spacing has no effect",
                    "Spacing only affects frequency"
                ],
                "correct": 1,
                "explanation": "Wider spacing generally captures a larger potential difference, increasing amplitude (up to a point)."
            },
            {
                "q": "What is the relationship between spatial resolution and electrode spacing in bipolar setups?",
                "options": [
                    "Closer spacing = better resolution",
                    "Wider spacing = better resolution",
                    "Resolution is independent of spacing",
                    "Resolution is only determined by the amplifier"
                ],
                "correct": 0,
                "explanation": "Closer electrodes \\\"see\\\" a smaller area, leading to finer spatial resolution."
            },
            {
                "q": "Which frequencies are most affected by signal cancellation in a bipolar recording?",
                "options": [
                    "Low frequencies",
                    "High frequencies",
                    "All frequencies equally",
                    "Cancellation does not occur"
                ],
                "correct": 0,
                "explanation": "Longer wavelength (lower frequency) signals are more likely to be similar at both electrodes and thus cancelled."
            },
            {
                "q": "In bipolar EEG, what is a 'phase reversal'?",
                "options": [
                    "A machine error",
                    "A sign of a focal abnormality",
                    "A normal artifact",
                    "A sign of sleep"
                ],
                "correct": 1,
                "explanation": "A phase reversal where identical waveforms point toward each other signifies a focal source of electrical activity."
            },
            {
                "q": "How does the orientation of bipolar electrodes relative to the muscle fibers affect the signal?",
                "options": [
                    "Parallel placement yields the highest signal",
                    "Perpendicular placement yields the highest signal",
                    "Orientation does not matter",
                    "Diagonal placement is best"
                ],
                "correct": 0,
                "explanation": "Placing electrodes parallel to the muscle fibers maximizes the detected potential difference as the action potential travels."
            },
            {
                "q": "What is the main cause of signal cancellation in closely spaced bipolar electrodes?",
                "options": [
                    "Poor skin prep",
                    "Common Mode Rejection",
                    "Broken wires",
                    "The signals arriving at both electrodes simultaneously"
                ],
                "correct": 3,
                "explanation": "If an action potential reaches both electrodes at the exact same time, the difference is zero."
            },
            {
                "q": "How do bipolar derivations in EEG differ from referential derivations?",
                "options": [
                    "Bipolar links adjacent electrodes in a chain",
                    "Bipolar uses a single common reference",
                    "Bipolar requires fewer electrodes",
                    "Bipolar cannot detect seizures"
                ],
                "correct": 0,
                "explanation": "Bipolar derivations typically connect electrodes in anterior-to-posterior or transverse chains."
            },
            {
                "q": "What is the typical bandwidth for surface EMG (bipolar)?",
                "options": [
                    "0.1 - 100 Hz",
                    "10 - 500 Hz",
                    "1000 - 5000 Hz",
                    "DC only"
                ],
                "correct": 1,
                "explanation": "Surface EMG typically contains useful information in the 10 - 500 Hz range."
            },
            {
                "q": "Why might a bipolar signal appear artificially small?",
                "options": [
                    "High CMRR",
                    "Electrodes placed on an inactive muscle",
                    "Electrodes placed very close together",
                    "All of the above"
                ],
                "correct": 3,
                "explanation": "All these factors contribute to a smaller differential signal."
            },
            {
                "q": "What is the primary benefit of bipolar over monopolar in noisy environments?",
                "options": [
                    "It acts as an antenna",
                    "High Common Mode Rejection Ratio (CMRR)",
                    "It requires a larger ground",
                    "It amplifies the noise"
                ],
                "correct": 1,
                "explanation": "CMRR effectively eliminates environmental noise picked up equally by both electrodes."
            }
        ],
        "Chapter 3": [
            {
                "q": "In routine EEG, what montage relies heavily on bipolar connections?",
                "options": [
                    "The ear reference montage",
                    "The average reference montage",
                    "The 'Double Banana' (Longitudinal Bipolar) montage",
                    "The monopolar montage"
                ],
                "correct": 2,
                "explanation": "The longitudinal bipolar montage connects electrodes in chains from front to back."
            },
            {
                "q": "Why is a bipolar montage useful for reading EEGs?",
                "options": [
                    "It measures absolute amplitudes accurately",
                    "It provides the best overall view of global activity",
                    "It is excellent for localizing sharp waves and spikes",
                    "It is the only legal way to record EEG"
                ],
                "correct": 2,
                "explanation": "Bipolar montages excel at highlighting localized differences, pointing clearly to the source of spikes via phase reversals."
            },
            {
                "q": "In NCV (Nerve Conduction Velocity) testing, why are bipolar stimulating electrodes used?",
                "options": [
                    "To measure the absolute potential of the nerve",
                    "To provide a localized stimulus current",
                    "To record the muscle response",
                    "To ground the patient"
                ],
                "correct": 1,
                "explanation": "Bipolar stimulators deliver a precise, localized current pulse to depolarize the nerve."
            },
            {
                "q": "What is a 'bipolar' pacemaker lead?",
                "options": [
                    "A lead with one electrode in the right atrium and one in the left",
                    "A lead with both the anode and cathode located within the heart chamber",
                    "A lead that uses the generator casing as the anode",
                    "A temporary pacemaker lead"
                ],
                "correct": 1,
                "explanation": "Bipolar leads have both electrodes near the tip, reducing the chance of extracardiac stimulation."
            },
            {
                "q": "Why might a surgeon prefer bipolar electrocautery over monopolar?",
                "options": [
                    "It cuts faster",
                    "It requires a grounding pad",
                    "The current paths are restricted to the tissue between the forceps tips",
                    "It is cheaper"
                ],
                "correct": 2,
                "explanation": "Bipolar cautery restricts current flow, minimizing damage to surrounding tissues."
            },
            {
                "q": "In high-density surface EMG, bipolar derivations are often formed retrospectively. Why?",
                "options": [
                    "To increase signal amplitude",
                    "To improve spatial selectivity and reduce crosstalk",
                    "To simplify the recording hardware",
                    "To measure absolute voltage"
                ],
                "correct": 1,
                "explanation": "Subtracting adjacent electrode signals improves focus and spatial resolution."
            },
            {
                "q": "How does the orientation of the bipolar pair affect the recording of a traveling action potential?",
                "options": [
                    "It has no effect",
                    "Parallel alignment gives the largest signal",
                    "Perpendicular alignment gives the largest signal",
                    "A 45-degree angle is optimal"
                ],
                "correct": 1,
                "explanation": "Parallel alignment allows the wave to pass each electrode sequentially, maximizing the difference."
            },
            {
                "q": "What is the primary diagnostic use of finding a phase reversal in a bipolar EEG montage?",
                "options": [
                    "Diagnosing sleep apnea",
                    "Localizing a seizure focus or epileptiform discharge",
                    "Determining brain death",
                    "Measuring cognitive workload"
                ],
                "correct": 1,
                "explanation": "Phase reversals firmly point to the highly localized generator of a spike."
            },
            {
                "q": "In needle EMG, is the recording typically bipolar or monopolar?",
                "options": [
                    "Strictly monopolar",
                    "Strictly bipolar",
                    "Usually monopolar reference with an active needle tip (or concentric bipolar)",
                    "Needle EMG does not use electrodes"
                ],
                "correct": 2,
                "explanation": "Concentric needles are essentially a very closely spaced bipolar configuration."
            },
            {
                "q": "Which recording application relies LEAST on bipolar setups?",
                "options": [
                    "Routine clinical ECG (12-lead)",
                    "Routine clinical EEG localization",
                    "Needle EMG",
                    "NCS/NCV studies"
                ],
                "correct": 0,
                "explanation": "While limb leads are bipolar, the precordial (chest) leads in a 12-lead ECG are unipolar (monopolar)."
            }
        ],
        "Chapter 4": [
            {
                "q": "Considering advanced artifact management, why might a high CMRR fail to reject 50/60 Hz noise in a bipolar setup?",
                "options": [
                    "The CMRR is too high",
                    "The active and reference electrodes have significantly different impedances",
                    "The ground electrode is fully functional",
                    "The amplifier is battery powered"
                ],
                "correct": 1,
                "explanation": "Impedance mismatch converts common-mode noise into a differential signal, defeating CMRR."
            },
            {
                "q": "What advanced filtering technique is most appropriate for removing ECG artifact from a bipolar trunk EMG recording while preserving the EMG bandwidth?",
                "options": [
                    "A simple 50 Hz notch filter",
                    "A high-pass filter set at 200 Hz",
                    "A simple low-pass filter set at 20 Hz",
                    "Template subtraction or adaptive filtering"
                ],
                "correct": 3,
                "explanation": "Adaptive filtering or template subtraction removes the specific ECG waveform without excessively distorting the overlapping EMG frequencies."
            },
            {
                "q": "In a bipolar recording, if motion artifact affects only ONE of the two electrodes, how will the differential amplifier respond?",
                "options": [
                    "It will reject the artifact entirely",
                    "It will amplify the artifact as part of the differential signal",
                    "It will shut down to protect the circuit",
                    "It will convert the artifact to a higher frequency"
                ],
                "correct": 1,
                "explanation": "CMRR only rejects noise common to BOTH electrodes. Noise on only one is treated as a valid signal."
            },
            {
                "q": "How does cable sway (triboelectric effect) introduce artifacts into a bipolar system?",
                "options": [
                    "By generating a magnetic field",
                    "By altering the ambient temperature",
                    "By causing variations in capacitance resulting in low-frequency voltage shifts",
                    "By decreasing CMRR directly"
                ],
                "correct": 2,
                "explanation": "Movement of the cables changes their capacitance, inducing small currents that the amplifier detects as low-frequency artifact."
            },
            {
                "q": "What advanced hardware mechanism mitigates cable motion artifacts?",
                "options": [
                    "Using longer cables",
                    "Using unshielded twisted pair cables",
                    "Using active electrodes equipped with pre-amplifiers at the recording site",
                    "Increasing the sampling rate"
                ],
                "correct": 3,
                "explanation": "Active electrodes amplify the signal before it travels down the cable, making it far less susceptible to triboelectric noise."
            },
            {
                "q": "When attempting to record low-amplitude, high-frequency EEG (e.g., Gamma band) using a bipolar setup, which artifact becomes intensely problematic?",
                "options": [
                    "ECG artifact",
                    "Eye blink (EOG) artifact",
                    "Cranial muscle (EMG) artifact",
                    "Respiration artifact"
                ],
                "correct": 2,
                "explanation": "Cranial muscle activity (EMG) shares the same high-frequency bandwidth as Gamma EEG and is often much higher in amplitude."
            },
            {
                "q": "A bipolar recording shows a sudden, massive, high-frequency clipping event lasting 2 seconds. What is the most likely cause?",
                "options": [
                    "A loose ground connection",
                    "Patient movement/postural shift",
                    "Electrocautery used nearby in the operating room",
                    "50/60 Hz mains interference"
                ],
                "correct": 2,
                "explanation": "Electrocautery (Bovie) generates massive high-frequency electrical noise that overwhelms standard amplifiers."
            },
            {
                "q": "What is the impact of an excessively aggressive Notch filter (e.g., 50 Hz) on a bipolar EMG signal?",
                "options": [
                    "It only removes the noise perfectly",
                    "It causes 'ringing' artifacts and removes valid EMG signal content at that frequency",
                    "It increases the overall amplitude of the EMG",
                    "It improves the CMRR of the amplifier"
                ],
                "correct": 1,
                "explanation": "Notch filters can distort the phase and remove physiological data that happens to fall near the notch frequency."
            },
            {
                "q": "Why is optimal skin preparation (lowering impedance <5 kOhm) arguably more critical for bipolar recordings than monopolar?",
                "options": [
                    "Because bipolar uses two electrodes that must be closely matched in impedance to maintain high CMRR",
                    "Because bipolar amplifiers are inherently weaker",
                    "Because bipolar recordings are always done on the scalp",
                    "It is not more critical; it is less critical"
                ],
                "correct": 0,
                "explanation": "High CMRR relies on balanced inputs. Unequal impedances unbalance the inputs, degrading noise rejection."
            },
            {
                "q": "If a 'wandering baseline' (low-frequency drift) is severe in a bipolar recording, what is the best initial approach BEFORE applying software filters?",
                "options": [
                    "Increase the high-pass filter cutoff (e.g.",
                    "from 0.1 Hz to 5 Hz)",
                    "Check electrode adhesion and consider re-prepping the skin",
                    "Decrease the low-pass filter cutoff",
                    "Increase the amplifier gain"
                ],
                "correct": 1,
                "explanation": "Fixing the physical source (poor contact or sweat) is always preferred over filtering, which can distort the signal."
            }
        ],
        "Chapter 5": [
            {
                "q": "In advanced mathematical modeling of bipolar EMG (e.g., using a volume conduction model), the recorded signal is represented as the convolution of the neural drive and what else?",
                "options": [
                    "The Common Mode Rejection Ratio",
                    "The Motor Unit Action Potential (MUAP) template",
                    "The electrode impedance",
                    "The sampling frequency"
                ],
                "correct": 1,
                "explanation": "The signal is modeled as the neural firing train convolved with the shape of the MUAP as it travels past the electrodes."
            },
            {
                "q": "High-Density sEMG (HD-sEMG) arrays can synthesize bipolar derivations in software. What advanced metric can be calculated by tracking the phase shift of a signal across multiple bipolar pairs?",
                "options": [
                    "The amplitude of the action potential",
                    "The frequency of the firing rate",
                    "The Muscle Fiber Conduction Velocity (MFCV)",
                    "The exact depth of the motor unit"
                ],
                "correct": 2,
                "explanation": "By knowing the distance between electrodes and measuring the time delay of the signal peak, conduction velocity can be calculated."
            },
            {
                "q": "When utilizing Independent Component Analysis (ICA) on multi-channel bipolar EEG data, what is the primary goal?",
                "options": [
                    "To increase the spatial resolution of the electrodes",
                    "To separate the mixed signals into independent underlying physiological or artifactual sources",
                    "To convert the bipolar data into monopolar data",
                    "To measure the impedance of the electrodes continuously"
                ],
                "correct": 1,
                "explanation": "ICA mathematically unmixes the channels to find independent generators (e.g., separating eyeblinks from brain waves)."
            },
            {
                "q": "In the context of the spatial transfer function of a bipolar electrode configuration, the system acts as what kind of spatial filter?",
                "options": [
                    "A spatial low-pass filter",
                    "A spatial high-pass filter",
                    "A spatial band-pass filter",
                    "It does not act as a spatial filter"
                ],
                "correct": 2,
                "explanation": "It rejects uniform signals (low spatial frequency) and very rapid spatial changes smaller than the electrode size (high spatial frequency)."
            },
            {
                "q": "Why does the amplitude of a bipolar recording decrease rapidly as the distance from the bioelectric source (e.g., the nerve or muscle fiber) increases?",
                "options": [
                    "Because the amplifier gain decreases automatically",
                    "Because the signal approaches the electrodes more perpendicularly",
                    "reducing the potential difference",
                    "Because the resistance of the skin increases",
                    "Because the ground becomes ineffective"
                ],
                "correct": 1,
                "explanation": "As the source gets further away, the distances to the two electrodes become more equal, resulting in a smaller difference in potential."
            },
            {
                "q": "What advanced signal processing technique is often applied to bipolar EMG signals to estimate the neural drive to the muscle (envelope extraction)?",
                "options": [
                    "Fast Fourier Transform (FFT)",
                    "Rectification followed by low-pass filtering",
                    "High-pass filtering only",
                    "Independent Component Analysis (ICA)"
                ],
                "correct": 1,
                "explanation": "Rectifying (making all values positive) and low-pass filtering creates an envelope that correlates with muscle force."
            },
            {
                "q": "In advanced EEG source localization (e.g., LORETA), why is bipolar data often converted or re-referenced to average reference or a common reference before analysis?",
                "options": [
                    "Because source localization algorithms require absolute potential estimates at each scalp location relative to a hypothetical zero",
                    "Because bipolar data contains too much noise",
                    "Because bipolar data cannot be digitized",
                    "Because the algorithms only work on single channels"
                ],
                "correct": 0,
                "explanation": "Source modeling requires an estimate of the field distribution, which is easier to compute from referential data than differential chains."
            },
            {
                "q": "According to the dipole model of bioelectric sources, a bipolar recording will record a zero-amplitude signal when the dipole axis is oriented how in relation to the electrode pair?",
                "options": [
                    "Parallel to the axis of the electrode pair",
                    "Perpendicular to the axis of the electrode pair",
                    "At a 45-degree angle",
                    "The orientation does not matter"
                ],
                "correct": 1,
                "explanation": "If the dipole is perpendicular, the potential at both electrodes is exactly the same, resulting in a zero difference."
            },
            {
                "q": "What is 'spatial aliasing' in the context of high-end bipolar array recordings?",
                "options": [
                    "When the sampling rate is too low in time",
                    "When the electrodes are spaced too far apart to accurately capture the spatial distribution of the potential field without ambiguity",
                    "When the amplifier gain is set too high",
                    "When a 50Hz notch filter is applied incorrectly"
                ],
                "correct": 1,
                "explanation": "Just as low temporal sampling causes temporal aliasing, sparse electrode placement causes spatial aliasing of the electrical field."
            },
            {
                "q": "In single-fiber EMG (a highly specialized bipolar-like technique), what specific parameter is being measured when calculating 'Jitter'?",
                "options": [
                    "The total amplitude of the motor unit",
                    "The variations in the time interval between action potentials of two muscle fibers in the same motor unit",
                    "The conduction velocity of the main nerve trunk",
                    "The frequency of the firing rate"
                ],
                "correct": 1,
                "explanation": "Jitter measures the micro-variations in neuromuscular transmission time."
            }
        ]
    },
    "Monopolar": {
        "Chapter 1": [
            {
                "q": "What defines a monopolar (referential) recording configuration?",
                "options": [
                    "Using two closely spaced active electrodes",
                    "Using one active electrode and one distant reference electrode",
                    "Using only a ground electrode",
                    "Using an array of 64 electrodes"
                ],
                "correct": 1,
                "explanation": "Monopolar recording compares the potential at an active site to a relatively neutral, distant reference site."
            },
            {
                "q": "What is the theoretical goal of the reference electrode in a monopolar setup?",
                "options": [
                    "To provide high-frequency noise",
                    "To measure the exact same signal as the active electrode",
                    "To represent a true 'zero' voltage potential",
                    "To serve as the safety ground"
                ],
                "correct": 2,
                "explanation": "Ideally, the reference should be electrically inactive, providing a stable zero point to measure absolute voltage changes at the active site."
            },
            {
                "q": "Which of the following describes the 'active' electrode in a monopolar system?",
                "options": [
                    "It is placed far from the signal source",
                    "It is placed directly over the anatomical area of interest",
                    "It is attached to the machine chassis",
                    "It never touches the patient"
                ],
                "correct": 1,
                "explanation": "The active (or exploring) electrode is placed over the generator of the bioelectric signal."
            },
            {
                "q": "Compared to a bipolar setup, what is a typical characteristic of a monopolar signal's amplitude?",
                "options": [
                    "It is generally much lower",
                    "It is generally higher",
                    "It is always exactly zero",
                    "It is inverted"
                ],
                "correct": 1,
                "explanation": "Because it measures against a distant reference rather than a nearby active area, it often captures the full amplitude of the potential field."
            },
            {
                "q": "What is a major vulnerability of monopolar recording?",
                "options": [
                    "It cannot detect low frequencies",
                    "It is highly susceptible to generalized environmental noise and distant biological signals (like ECG)",
                    "It requires too many electrodes to set up",
                    "It only works on the limbs"
                ],
                "correct": 1,
                "explanation": "Because the distance between active and reference is large, the configuration acts like a large antenna for noise."
            },
            {
                "q": "In a 12-lead ECG, the precordial (chest) leads V1-V6 are examples of what type of recording?",
                "options": [
                    "Bipolar",
                    "Monopolar (Referential)",
                    "Quadripolar",
                    "Intracellular"
                ],
                "correct": 1,
                "explanation": "They use a single active chest electrode referenced to Wilson's Central Terminal."
            },
            {
                "q": "What is Wilson's Central Terminal (WCT)?",
                "options": [
                    "A specific node in the brain",
                    "A brand of amplifier",
                    "A calculated reference point made by combining signals from the Right Arm",
                    "Left Arm",
                    "and Left Leg",
                    "The ground electrode on an ECG machine"
                ],
                "correct": 2,
                "explanation": "WCT acts as a theoretical 'zero' reference for the monopolar chest leads in an ECG."
            },
            {
                "q": "Where is a common physical placement for a reference electrode in clinical EEG?",
                "options": [
                    "Over the occipital lobe",
                    "On the chest",
                    "On the earlobes or mastoid processes",
                    "On the tip of the nose"
                ],
                "correct": 2,
                "explanation": "The earlobes/mastoids are relatively inactive compared to the scalp, making them common reference sites."
            },
            {
                "q": "Does a true, absolutely zero-voltage biological reference exist on the human body?",
                "options": [
                    "Yes",
                    "the right leg",
                    "Yes",
                    "the earlobes",
                    "No",
                    "every part of the body has some electrical fluctuating potential",
                    "Yes",
                    "the tip of the nose"
                ],
                "correct": 2,
                "explanation": "Every 'reference' site is relatively inactive, not absolutely biologically silent."
            },
            {
                "q": "If an exploring electrode is placed on the scalp and the reference is placed on the wrist, what major artifact will likely ruin the EEG recording?",
                "options": [
                    "Eye blink artifact",
                    "ECG (heart) artifact",
                    "50/60 Hz power line noise",
                    "Sweat artifact"
                ],
                "correct": 1,
                "explanation": "The large distance between the head and wrist will capture the massive electrical signal of the heart located between them."
            }
        ],
        "Chapter 2": [
            {
                "q": "Why does monopolar recording generally exhibit poorer spatial resolution than bipolar recording?",
                "options": [
                    "The monopolar amplifiers are lower quality",
                    "The monopolar setup detects activity from a very large volume of tissue between the active and distant reference",
                    "Monopolar only uses one wire",
                    "Monopolar filters out high frequencies"
                ],
                "correct": 1,
                "explanation": "The large distance 'sees' a wide field, making it hard to pinpoint exactly where a signal is originating within that field."
            },
            {
                "q": "What happens to a monopolar recording if the supposedly 'inactive' reference electrode picks up a strong biological signal?",
                "options": [
                    "The signal is ignored by the amplifier",
                    "The signal from the reference will appear in the final output",
                    "often inverted",
                    "mimicking activity at the active site",
                    "The system will automatically shut down",
                    "The spatial resolution improves"
                ],
                "correct": 1,
                "explanation": "This is called an 'active reference' problem. The amplifier subtracts the reference from the active; if the reference has a spike, it looks like an inverted spike at the active site."
            },
            {
                "q": "In EEG, what is the 'Linked Ears' reference?",
                "options": [
                    "Using only one earlobe as a reference",
                    "Physically or mathematically connecting both earlobe electrodes together to serve as a single combined reference",
                    "Connecting the ears to the ground",
                    "A bipolar montage"
                ],
                "correct": 1,
                "explanation": "Linking ears helps to create a more central, balanced reference point for both hemispheres of the brain."
            },
            {
                "q": "What is an 'Average Reference' in EEG?",
                "options": [
                    "Averaging the signals of three specific electrodes",
                    "Calculating the average potential of all scalp electrodes and using that value as the reference for each individual electrode",
                    "Taking the mathematical mean of the patient's heart rate",
                    "Using the ground electrode as the reference"
                ],
                "correct": 1,
                "explanation": "This assumes that the sum of all electrical activity across the head at any given moment is roughly zero."
            },
            {
                "q": "When diagnosing ST-segment elevation myocardial infarction (STEMI), why are the monopolar precordial leads (V1-V6) critical?",
                "options": [
                    "They reject noise better than bipolar leads",
                    "They measure the absolute voltage changes in specific localized areas of the heart wall",
                    "They are easier to apply quickly",
                    "They measure the heart rate more accurately"
                ],
                "correct": 1,
                "explanation": "Monopolar leads measure absolute potential fields, which is necessary to detect the baseline shifts seen in ischemia."
            },
            {
                "q": "Which configuration is MORE prone to 50/60 Hz powerline interference?",
                "options": [
                    "Bipolar with 2cm spacing",
                    "Monopolar with active on head and reference on shoulder",
                    "They are equally prone",
                    "Neither is prone if a ground is used"
                ],
                "correct": 1,
                "explanation": "A larger physical loop area between the active and reference electrodes acts as a larger antenna for electromagnetic interference."
            },
            {
                "q": "In evoked potentials (e.g., Auditory Brainstem Responses), monopolar recordings are often used. Why?",
                "options": [
                    "Because spatial resolution is the most important factor",
                    "Because the generators are deep in the brainstem",
                    "and monopolar setups capture a wider",
                    "deeper field of view",
                    "Because it is required by law",
                    "Because the signals are very high amplitude"
                ],
                "correct": 1,
                "explanation": "Deep generators create widespread, low-amplitude surface fields that broad monopolar setups can detect better than narrow bipolar ones."
            },
            {
                "q": "What is the primary visual difference between a focal spike viewed in a bipolar montage versus a referential montage?",
                "options": [
                    "Bipolar shows a phase reversal; referential shows the highest amplitude at the active site",
                    "Bipolar shows high amplitude; referential shows a phase reversal",
                    "There is no visual difference",
                    "Referential montages cannot show spikes"
                ],
                "correct": 0,
                "explanation": "Referential montages rely on amplitude mapping to find the source focus, while bipolar uses phase reversals."
            },
            {
                "q": "If an EEG technologist notices ECG artifact in EVERY channel of a referential montage, what is the most likely culprit?",
                "options": [
                    "The patient is moving their eyes",
                    "Every active electrode is loose",
                    "The reference electrode is picking up the ECG and distributing it to all channels",
                    "The amplifier is broken"
                ],
                "correct": 2,
                "explanation": "An active or noisy reference contaminates every channel it is referenced against."
            },
            {
                "q": "How does an increase in the distance between the active and reference electrodes affect the recorded signal amplitude of a local source?",
                "options": [
                    "It generally decreases the amplitude",
                    "It generally increases the amplitude until the reference is completely outside the electric field of the source",
                    "It has absolutely no effect",
                    "It completely eliminates the signal"
                ],
                "correct": 1,
                "explanation": "Moving the reference further away from the active field ensures it is truly at a 'zero' potential relative to the source."
            }
        ],
        "Chapter 3": [
            {
                "q": "In clinical EEG mapping (topography), why is referential (monopolar) data almost exclusively used to generate the color maps instead of bipolar data?",
                "options": [
                    "Bipolar data cannot be digitized.",
                    "Referential data provides a measure of absolute potential at each point",
                    "allowing for interpolation and smooth gradient mapping.",
                    "Referential data uses fewer electrodes.",
                    "Mapping software is not advanced enough to use bipolar data."
                ],
                "correct": 1,
                "explanation": "Topographic maps require absolute voltage values at discrete points to draw contours; bipolar data only provides the slope (difference) between points, which cannot be directly mapped as a static field."
            },
            {
                "q": "When placing a reference electrode for an electroretinogram (ERG) to measure eye potentials, where should the reference ideally be placed?",
                "options": [
                    "Directly on the cornea",
                    "As far away on the body as possible",
                    "like the ankle",
                    "Close to the eye but electrically inactive",
                    "such as the temple or forehead (ipsilateral)",
                    "On the occipital lobe"
                ],
                "correct": 2,
                "explanation": "A nearby reference minimizes distant biological artifacts (like ECG) while still being outside the immediate high-amplitude field of the retina."
            },
            {
                "q": "In a unipolar (monopolar) pacing system for the heart, what acts as the reference (anode)?",
                "options": [
                    "The tip of the lead in the myocardium",
                    "A second ring on the lead inside the heart",
                    "The titanium casing of the implanted pulse generator (the 'can')",
                    "A surface electrode on the patient's skin"
                ],
                "correct": 2,
                "explanation": "Unipolar pacemakers use the large metal housing of the device itself, implanted in the chest wall, as the return path for the current."
            },
            {
                "q": "What is a major clinical disadvantage of unipolar cardiac pacemakers compared to bipolar?",
                "options": [
                    "They require higher battery voltage.",
                    "They are much larger in size.",
                    "They are highly susceptible to electromagnetic interference (EMI) and sensing skeletal muscle activity (myopotentials) as cardiac events.",
                    "They cannot pace the ventricle."
                ],
                "correct": 2,
                "explanation": "The large antenna formed between the heart tip and the distant generator casing makes unipolar systems very sensitive to external noise and pectoral muscle twitches."
            },
            {
                "q": "During an intraoperative neuromonitoring (IONM) procedure, a monopolar stimulating probe is used to find a nerve. Why?",
                "options": [
                    "It limits the spread of current to a very tiny area.",
                    "It produces a wide field of current spread",
                    "making it easier to trigger the nerve even if not perfectly localized.",
                    "It prevents the patient from moving.",
                    "It requires less electrical current."
                ],
                "correct": 1,
                "explanation": "Monopolar stimulators have a wide current spread, useful for general mapping and finding nerves in a region before precise bipolar localization."
            },
            {
                "q": "In deep brain stimulation (DBS), a monopolar configuration might be chosen over bipolar to achieve what clinical effect?",
                "options": [
                    "To minimize the volume of tissue activated (VTA)",
                    "To increase battery life",
                    "To create a larger",
                    "spherical volume of tissue activation (VTA) to cover a broader target area",
                    "To perfectly restrict stimulation to a 1mm radius"
                ],
                "correct": 2,
                "explanation": "Monopolar DBS (contact to case) creates a wider, more spherical electrical field compared to the localized field of bipolar stimulation."
            },
            {
                "q": "When analyzing the morphology of a specific brain wave (like a vertex sharp wave), why do neurophysiologists often look at a referential montage?",
                "options": [
                    "Because referential montages filter out high frequencies.",
                    "Because it shows the true wave shape and maximal amplitude without the distortion caused by subtracting adjacent active sites.",
                    "Because bipolar montages are only used during sleep.",
                    "They actually never look at referential montages for morphology."
                ],
                "correct": 1,
                "explanation": "Bipolar subtraction can distort the shape of a waveform, making referential the preferred choice for studying true morphology."
            },
            {
                "q": "If an EEG uses the 'Common Average Reference' (CAR), what happens if one electrode becomes extremely noisy or detaches?",
                "options": [
                    "Only that single channel is affected.",
                    "The machine automatically switches to a bipolar montage.",
                    "The noise is distributed into the average and contaminates every single channel in the recording.",
                    "The amplifier shuts down."
                ],
                "correct": 2,
                "explanation": "Because the CAR is built by summing all channels, a massive artifact in one channel alters the reference value used for all others."
            },
            {
                "q": "In clinical practice, what is the 'Laplacian' or 'Source Derivation' reference?",
                "options": [
                    "Using the ground as the reference.",
                    "Using the patient's nose as the reference.",
                    "A mathematical referencing technique where each electrode is referenced to the weighted average of its immediate surrounding neighbors.",
                    "A physical wire connecting the ears."
                ],
                "correct": 2,
                "explanation": "The Laplacian derivation acts as a high-pass spatial filter, greatly enhancing spatial resolution similarly to a tightly spaced bipolar setup, but computed from monopolar data."
            },
            {
                "q": "Why are the 6 precordial leads (V1-V6) of a 12-lead ECG considered unipolar (monopolar) when they are actually connected to a differential amplifier?",
                "options": [
                    "Because they only use one wire.",
                    "Because the 'reference' input of the amplifier is connected to Wilson's Central Terminal",
                    "an electrically neutral combined virtual ground",
                    "rather than a single active site.",
                    "Because they don't use a ground electrode.",
                    "Because they only measure the right side of the heart."
                ],
                "correct": 1,
                "explanation": "They measure the electrical potential at the chest site relative to a central (nearly zero) reference point, defining the unipolar concept."
            }
        ],
        "Chapter 4": [
            {
                "q": "Consider a monopolar recording in a highly unshielded environment. Why might twisting the active and reference wires together fail to eliminate 50/60 Hz magnetic interference?",
                "options": [
                    "Twisting wires only prevents biological noise.",
                    "If the active and reference electrodes are far apart on the body",
                    "a large physical loop area exists on the patient's body itself",
                    "regardless of the wires.",
                    "Twisting wires increases the antenna effect.",
                    "Magnetic interference cannot affect monopolar recordings."
                ],
                "correct": 1,
                "explanation": "The loop area susceptible to Faraday induction includes the path through the patient's tissue. Wide electrode spacing creates a large loop."
            },
            {
                "q": "In advanced EEG processing, what is the 'Reference Electrode Standardization Technique' (REST)?",
                "options": [
                    "A method for cleaning electrodes to medical standards.",
                    "A hardware standard for the size of the reference disc.",
                    "A computational method to re-reference scalp EEG recordings to a point at infinity",
                    "aiming for a true zero reference.",
                    "A method for restricting patient movement during resting state EEG."
                ],
                "correct": 2,
                "explanation": "REST uses physics and volume conduction models to mathematically project the reference to infinity, eliminating the 'active reference' problem."
            },
            {
                "q": "You are recording monopolar EEG. The reference is placed on the right earlobe (A2). You observe large, synchronous spikes in channels F4-A2, C4-A2, O2-A2, and surprisingly, inverted spikes in channels F3-A1, C3-A1. What is the advanced diagnosis of this artifact?",
                "options": [
                    "The patient is having a generalized seizure.",
                    "The active electrodes on the right side are all loose.",
                    "The right earlobe reference (A2) is contaminated by a temporal lobe spike",
                    "injecting inverted artifact into the contralateral channels.",
                    "The amplifier is rejecting the signal."
                ],
                "correct": 2,
                "explanation": "An active reference injects its signal into all channels. Channels referenced to the bad reference show the spike; channels on the other side might reflect it due to linked ears or referencing schemes."
            },
            {
                "q": "When attempting to extract deep, low-amplitude sources (like the brainstem) using monopolar EEG, what technique is mandatory to overcome the low Signal-to-Noise Ratio (SNR)?",
                "options": [
                    "Using only a 50Hz notch filter.",
                    "Signal averaging across hundreds or thousands of time-locked stimulus presentations.",
                    "Increasing the amplifier gain until the signal is visible.",
                    "Using a high-pass filter of 100Hz."
                ],
                "correct": 1,
                "explanation": "Averaging time-locked responses cancels out random background noise, allowing the consistent, tiny deep signals to emerge from the noise floor."
            },
            {
                "q": "In intraoperative monitoring, a monopolar stimulator is causing massive stimulus artifact that saturates the recording amplifiers, making it impossible to see the immediate nerve response. What hardware solution is best suited to fix this?",
                "options": [
                    "Applying a software low-pass filter.",
                    "Increasing the distance between the recording electrodes.",
                    "Using an amplifier with a fast-recovery circuit or stimulus blanking hardware.",
                    "Using a larger ground electrode."
                ],
                "correct": 2,
                "explanation": "Stimulus blanking disconnects or grounds the amplifier inputs for the exact millisecond the shock is delivered, preventing saturation."
            },
            {
                "q": "Why is the Common Mode Rejection Ratio (CMRR) functionally less effective in reducing powerline noise in a typical widespread monopolar setup compared to a closely spaced bipolar setup?",
                "options": [
                    "Monopolar amplifiers are built with cheaper components that lack high CMRR capability.",
                    "The noise fields (electric and magnetic) are often identical across the large distance between active and reference.",
                    "The electrical characteristics (impedance) and noise phase are vastly different at the widely separated active and reference sites",
                    "converting common-mode noise to differential noise.",
                    "CMRR only works for frequencies above 1000 Hz."
                ],
                "correct": 2,
                "explanation": "CMRR requires the noise to be perfectly identical at both inputs. Wide physical separation guarantees the noise will be different in amplitude and phase at each electrode."
            },
            {
                "q": "An artifact appears only in specific derivations using an Average Reference. How can you mathematically prove that the artifact is originating from ONE specific noisy active electrode driving the average, rather than being a true generalized physiological event?",
                "options": [
                    "It is impossible to prove without taking the electrodes off the patient.",
                    "The amplitude of the artifact will be highest in the channel of the defective electrode",
                    "and present but at a much lower",
                    "inverted amplitude in all other channels.",
                    "The artifact will have a completely different frequency in every channel.",
                    "The artifact will only appear when the patient moves."
                ],
                "correct": 1,
                "explanation": "The noisy electrode contributes 1/N to the average. It dominates its own channel, but subtracts uniformly (and inverted) from all others."
            },
            {
                "q": "What advanced spatial filtering technique addresses the 'smearing' effect of volume conduction through the skull, effectively turning monopolar scalp potentials into high-resolution cortical source estimates?",
                "options": [
                    "Fast Fourier Transform (FFT)",
                    "Simple Low-Pass Filtering",
                    "Surface Laplacian (Current Source Density) computation",
                    "Independent Component Analysis (ICA)"
                ],
                "correct": 2,
                "explanation": "The Surface Laplacian acts as a powerful spatial high-pass filter, emphasizing local cortical activity directly beneath the electrode and rejecting widespread volume conduction."
            },
            {
                "q": "During monopolar pacing, 'muscle twitch' (pectoral stimulation) is a known side effect. This is because the current travels from the heart tip to the generator casing. What programming parameter change is the FIRST line of defense if this occurs?",
                "options": [
                    "Increase the stimulation voltage.",
                    "Switch the pacemaker to a bipolar pacing configuration (if the lead supports it).",
                    "Prescribe muscle relaxants.",
                    "Change the pacing rate."
                ],
                "correct": 1,
                "explanation": "Switching to bipolar confines the current to the tip of the lead inside the heart, completely eliminating the path through the pectoral muscle."
            },
            {
                "q": "When measuring extremely low-frequency biological signals (like DC potentials or galvanic skin response) using a monopolar setup, what specific hardware artifact must be aggressively managed?",
                "options": [
                    "High-frequency electromagnetic interference.",
                    "Electrode polarization half-cell potentials fluctuating due to temperature or movement.",
                    "50/60 Hz powerline noise.",
                    "Cross-talk from adjacent muscles."
                ],
                "correct": 1,
                "explanation": "Standard electrodes act like small batteries (half-cells). Fluctuations in this DC voltage mimic the slow biological signals being measured. Non-polarizing electrodes (like Ag/AgCl) are required."
            }
        ],
        "Chapter 5": [
            {
                "q": "In advanced source localization using High-Density EEG (e.g., 256 channels), why is accurate head modeling (Boundary Element Method or Finite Element Method) absolutely critical when using monopolar referenced data?",
                "options": [
                    "To make the 3D images look attractive.",
                    "Because the skull acts as a highly resistive layer that smears the absolute electric field; models are needed to reverse this volume conduction effect.",
                    "Because the models automatically remove 50Hz noise.",
                    "Because bipolar data doesn't require models."
                ],
                "correct": 1,
                "explanation": "Monopolar data gives the surface field, but to find the internal source, complex physics models must 'un-smear' the effects of the skull and scalp resistance."
            },
            {
                "q": "When defining a reference for a high-density, whole-head monopolar recording, the 'Common Average Reference' (CAR) is popular. What is the fundamental mathematical assumption underlying CAR?",
                "options": [
                    "That the brain generates no electrical activity.",
                    "That the sum of the potentials over the entire surface of a closed sphere containing a dipole source is zero.",
                    "That the reference electrode has zero impedance.",
                    "That the patient is perfectly grounded."
                ],
                "correct": 1,
                "explanation": "If you sample the entire sphere adequately, the positive and negative poles of the internal dipole fields cancel out when averaged."
            },
            {
                "q": "What is the 'zero-integral' problem encountered when using the Common Average Reference (CAR) on a standard clinical 19-channel EEG?",
                "options": [
                    "The EEG machine crashes if the sum is exactly zero.",
                    "19 channels do not adequately cover the lower half of the head/sphere",
                    "violating the assumption that the integral of the surface potential is zero",
                    "leading to a biased reference.",
                    "The electrodes cannot integrate signals.",
                    "The ground electrode causes the average to equal zero."
                ],
                "correct": 1,
                "explanation": "Because the face and neck are not recorded, the sampled area is incomplete, and the average is not a true 'zero'."
            },
            {
                "q": "In the context of the forward problem in electrophysiology, the potential measured at a monopolar surface electrode is the dot product of the dipole moment vector and what other vector?",
                "options": [
                    "The velocity vector of the action potential.",
                    "The lead field vector (which describes how a source at that specific location projects to that specific electrode).",
                    "The acceleration vector of the patient.",
                    "The magnetic field vector."
                ],
                "correct": 1,
                "explanation": "The lead field matrix mathematically links the internal source generator space to the external sensor space."
            },
            {
                "q": "When using Independent Component Analysis (ICA) to remove ECG artifact from monopolar EEG, the ICA algorithm separates the data into independent components. How does one theoretically identify the 'ECG component'?",
                "options": [
                    "It is the component with the lowest amplitude.",
                    "It is the component with the highest frequency.",
                    "It is the component whose time course strongly correlates with the QRS complex and whose spatial topography matches the projection of the heart's vector to the scalp.",
                    "It is always Component #1."
                ],
                "correct": 2,
                "explanation": "ICA isolates the source. You identify it by its timing (matching the heartbeat) and its spatial pattern across the head."
            },
            {
                "q": "What advanced mathematical transformation is used to convert monopolar (referential) potential data into Current Source Density (CSD), completely eliminating the need for a physical reference electrode?",
                "options": [
                    "The Fast Fourier Transform.",
                    "The spatial second derivative (Laplacian) of the surface potential distribution.",
                    "A simple high-pass filter.",
                    "The wavelet transform."
                ],
                "correct": 1,
                "explanation": "The Laplacian computes the sinks and sources of current entering and exiting the scalp, which is independent of the reference."
            },
            {
                "q": "In Brain-Computer Interfaces (BCI) relying on P300 event-related potentials, monopolar recordings referenced to the mastoids are common. What signal processing step is critical to extract the microvolt-level P300 signal from the much larger background EEG?",
                "options": [
                    "Applying a 50Hz notch filter.",
                    "Using Independent Component Analysis.",
                    "Time-domain signal averaging locked to the onset of the target stimulus.",
                    "Converting the signal to the frequency domain (FFT)."
                ],
                "correct": 2,
                "explanation": "Averaging perfectly aligned responses cancels out the random background EEG, revealing the consistent P300 wave."
            },
            {
                "q": "A researcher is studying very slow cortical potentials (DC shifts) occurring over several seconds prior to a voluntary movement (Bereitschaftspotential). Which type of monopolar electrode is absolutely required to prevent the signal from disappearing?",
                "options": [
                    "Standard gold cup electrodes.",
                    "Dry active electrodes.",
                    "Non-polarizing Ag/AgCl electrodes coupled with a DC-coupled amplifier.",
                    "Platinum-iridium needle electrodes."
                ],
                "correct": 2,
                "explanation": "Standard electrodes polarize and act as high-pass filters, destroying slow DC shifts. Ag/AgCl avoids this."
            },
            {
                "q": "When estimating functional connectivity (e.g., coherence or phase-locking) between two cortical regions using monopolar EEG, what major pitfall causes spurious, artificially high connectivity measurements?",
                "options": [
                    "High electrode impedance.",
                    "Volume conduction spreading the signal from a single deep source to both surface electrodes simultaneously.",
                    "Using too many electrodes.",
                    "The patient falling asleep."
                ],
                "correct": 1,
                "explanation": "A single source deep in the brain spreads via volume conduction to hit both electrodes at the exact same time, making them appear highly 'connected' when they just share a source."
            },
            {
                "q": "How does the 'Reference Electrode Standardization Technique' (REST) attempt to solve the volume conduction connectivity problem mentioned above?",
                "options": [
                    "By physically shielding the electrodes.",
                    "By re-referencing the data to a point at infinity",
                    "which mathematically reduces the widespread projection of deep sources compared to a surface reference.",
                    "By only using bipolar derivations.",
                    "By applying a low-pass filter."
                ],
                "correct": 1,
                "explanation": "By projecting the reference to infinity, REST removes the correlation artificially injected into all channels by a shared physical surface reference."
            }
        ]
    }
};