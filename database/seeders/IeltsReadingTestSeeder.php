<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Test;
use Illuminate\Database\Seeder;

/**
 * Seeds the client's real IELTS Academic Reading Practice Test 1
 * (Start Smart Future) — passages, questions and answer keys transcribed
 * from the client's own documents in requirements-and-sampletests:
 *   - "Reading Practice Test 1.pdf"        (question paper)
 *   - "Reading Practice Test 1 - Answers.pdf" (official answer key)
 *
 * SCOPE: the 26 option-based questions (1–8, 14–21, 26, 27–35), so the
 * test totals 26 marks for now. The paper's other 14 questions
 * (9–13, 22–25, 36–40) are typed sentence/summary completions, which the
 * current engine cannot score (qtype enum: mcq / true_false /
 * multi_select). Their texts and official answers are recorded in the
 * comment block at the bottom of this file; they slot in at sort_order
 * 9–13, 22–25 and 36–40 once a text_input qtype exists.
 *
 * Each passage's full text is stored in `passage_text` on the FIRST
 * question of its section (Q1, Q14, Q27).
 *
 * Idempotent: firstOrCreate for subject/module/test; questions are only
 * created when the test has none (same convention as ContentSeeder).
 */
class IeltsReadingTestSeeder extends Seeder
{
    public function run(): void
    {
        $subject = Subject::firstOrCreate(
            ['slug' => 'ielts-academic'],
            ['name' => 'IELTS Academic', 'sort_order' => 2]
        );

        $module = Module::firstOrCreate(
            ['subject_id' => $subject->id, 'name' => 'Reading Module'],
            ['description' => 'IELTS Academic Reading practice tests.', 'sort_order' => 1]
        );

        $test = Test::firstOrCreate(
            ['module_id' => $module->id, 'title' => 'Reading Practice Test 1'],
            [
                'description' => "Full IELTS Academic Reading Test covering 3 passages: Aphantasia, Life Lessons from Villains, and Britain's TV Industry.",
                'kind' => 'practice',
                'timed' => true,
                'duration_minutes' => 60,
                'is_free' => true,
                'price' => null,
                'status' => 'published',
            ]
        );

        if ($test->questions()->exists()) {
            return;
        }

        foreach ($this->questions() as $question) {
            Question::create(array_merge($question, [
                'test_id' => $test->id,
                'marks' => 1,
            ]));
        }
    }

    /**
     * Questions keyed by their number on the paper (used as sort_order,
     * so the gaps 9–13, 22–25, 36–40 stay reserved for the typed
     * completion questions).
     */
    private function questions(): array
    {
        $tfng = ['TRUE', 'FALSE', 'NOT GIVEN']; // indices: TRUE=0, FALSE=1, NOT GIVEN=2
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        return [
            // ── Passage 1: Aphantasia — Questions 1–8 (True / False / Not Given) ──
            [
                'sort_order' => 1,
                'qtype' => 'mcq',
                'question_text' => 'Aphantasia is a condition, which describes people, for whom it is hard to visualise mental images.',
                'passage_text' => $this->passageOne(),
                'options' => $tfng,
                'correct_options' => [1], // FALSE
            ],
            [
                'sort_order' => 2,
                'qtype' => 'mcq',
                'question_text' => 'Niel Kenmuir was unable to count sheep in his head.',
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 3,
                'qtype' => 'mcq',
                'question_text' => 'People with aphantasia struggle to remember personal traits and clothes of different people.',
                'options' => $tfng,
                'correct_options' => [2], // NOT GIVEN
            ],
            [
                'sort_order' => 4,
                'qtype' => 'mcq',
                'question_text' => 'Niel regrets that he cannot portray an image of his fiancée in his mind.',
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 5,
                'qtype' => 'mcq',
                'question_text' => "Inability to picture things in someone's head is often a cause of distress for a person.",
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 6,
                'qtype' => 'mcq',
                'question_text' => "All people with aphantasia start to feel 'isolated' or 'alone' at some point of their lives.",
                'options' => $tfng,
                'correct_options' => [2], // NOT GIVEN
            ],
            [
                'sort_order' => 7,
                'qtype' => 'mcq',
                'question_text' => "Lauren Beard's career depends on her imagination.",
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 8,
                'qtype' => 'mcq',
                'question_text' => 'The author met Lauren Beard when she was working on a comedy scene in her next book.',
                'options' => $tfng,
                'correct_options' => [1], // FALSE
            ],

            // ── Passage 2: Life lessons from villains — Questions 14–21 ──
            // Paragraph heading matching: the question text is the heading,
            // the answer is the paragraph letter (options A–H).
            [
                'sort_order' => 14,
                'qtype' => 'mcq',
                'question_text' => 'Jailbreak with creative thinking',
                'passage_text' => $this->passageTwo(),
                'options' => $letters,
                'correct_options' => [2], // C
            ],
            [
                'sort_order' => 15,
                'qtype' => 'mcq',
                'question_text' => 'Five common traits among rule-breakers',
                'options' => $letters,
                'correct_options' => [6], // G
            ],
            [
                'sort_order' => 16,
                'qtype' => 'mcq',
                'question_text' => 'Comparison between criminals and traditional businessmen',
                'options' => $letters,
                'correct_options' => [1], // B
            ],
            [
                'sort_order' => 17,
                'qtype' => 'mcq',
                'question_text' => "Can drug baron's escape teach legitimate corporations?",
                'options' => $letters,
                'correct_options' => [0], // A
            ],
            [
                'sort_order' => 18,
                'qtype' => 'mcq',
                'question_text' => 'Great entrepreneur',
                'options' => $letters,
                'correct_options' => [7], // H
            ],
            [
                'sort_order' => 19,
                'qtype' => 'mcq',
                'question_text' => 'How criminal groups deceive the law',
                'options' => $letters,
                'correct_options' => [3], // D
            ],
            [
                'sort_order' => 20,
                'qtype' => 'mcq',
                'question_text' => 'The difference between legal and illegal organisations',
                'options' => $letters,
                'correct_options' => [4], // E
            ],
            [
                'sort_order' => 21,
                'qtype' => 'mcq',
                'question_text' => 'Similarity between criminals and start-up founders',
                'options' => $letters,
                'correct_options' => [5], // F
            ],
            // Question 26 — MCQ on Passage 2.
            [
                'sort_order' => 26,
                'qtype' => 'mcq',
                'question_text' => 'The main goal of this article is to:',
                'options' => [
                    'Show different ways of illegal activity',
                    'Justify criminals with creative thinking',
                    'Give an overview of various criminals and their gangs',
                    'Draw a comparison between legal and illegal business, providing examples',
                ],
                'correct_options' => [2], // C
            ],

            // ── Passage 3: Britain needs strong TV industry ──
            // Questions 27–31 (True / False / Not Given)
            [
                'sort_order' => 27,
                'qtype' => 'mcq',
                'question_text' => 'Armando Iannucci expressed a need of having more popular channels.',
                'passage_text' => $this->passageThree(),
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 28,
                'qtype' => 'mcq',
                'question_text' => 'John Whittingdale wanted to dismantle the BBC.',
                'options' => $tfng,
                'correct_options' => [1], // FALSE
            ],
            [
                'sort_order' => 29,
                'qtype' => 'mcq',
                'question_text' => 'Iannucci delivered the 30th annual MacTaggart Lecture.',
                'options' => $tfng,
                'correct_options' => [2], // NOT GIVEN
            ],
            [
                'sort_order' => 30,
                'qtype' => 'mcq',
                'question_text' => 'Ianucci believes that British television has contributed to the success of American Tv shows.',
                'options' => $tfng,
                'correct_options' => [0], // TRUE
            ],
            [
                'sort_order' => 31,
                'qtype' => 'mcq',
                'question_text' => 'There have been negotiations over the future of the BBC in July.',
                'options' => $tfng,
                'correct_options' => [1], // FALSE
            ],
            // Questions 32–35 — MCQs on Passage 3.
            [
                'sort_order' => 32,
                'qtype' => 'mcq',
                'question_text' => 'Ianucci praised everything EXCEPT',
                'options' => ['US Shows', 'British Shows', 'Corporation', 'British Programming'],
                'correct_options' => [0], // A
            ],
            [
                'sort_order' => 33,
                'qtype' => 'mcq',
                'question_text' => 'To advise on the charter renewal Mr Whittingdale appointed a panel of',
                'options' => ['Five people', 'Two people', 'Seven people', 'Four people'],
                'correct_options' => [2], // C
            ],
            [
                'sort_order' => 34,
                'qtype' => 'mcq',
                'question_text' => 'Who of these people was NOT invited to the discussion concerning BBC renewal?',
                'options' => ['Armando Iannucci', 'Dawn Airey', 'John Whittingdale', 'Stewart Purvis'],
                'correct_options' => [0], // A
            ],
            [
                'sort_order' => 35,
                'qtype' => 'mcq',
                'question_text' => 'There panel of experts lacks:',
                'options' => ['Media owners', 'People who make enduring Tv shows', 'Gurus of Television industry', 'Top Executives'],
                'correct_options' => [1], // B
            ],
        ];
    }

    /**
     * Reading Passage 1 — "Aphantasia: A life without mental images"
     * (transcribed from the client's question paper).
     */
    private function passageOne(): string
    {
        return <<<'TEXT'
Close your eyes and imagine walking along a sandy beach and then gazing over the horizon as the Sun rises. How clear is the image that springs to mind? Most people can readily conjure images inside their head — known as their mind's eye. But this year scientists have described a condition, aphantasia, in which some people are unable to visualise mental images.

Niel Kenmuir, from Lancaster, has always had a blind mind's eye. He knew he was different even in childhood. "My stepfather, when I couldn't sleep, told me to count sheep, and he explained what he meant, I tried to do it and I couldn't," he says. "I couldn't see any sheep jumping over fences, there was nothing to count."

Our memories are often tied up in images: think back to a wedding or first day at school. As a result, Niel admits, some aspects of his memory are "terrible", but he is very good at remembering facts. And, like others with aphantasia, he struggles to recognise faces. Yet he does not see aphantasia as a disability, but simply a different way of experiencing life.

Mind's eye blind

Ironically, Niel now works in a bookshop, although he largely sticks to the non-fiction aisles. His condition begs the question: what is going on inside his picture-less mind? I asked him what happens when he tries to picture his fiancée. "This is the hardest thing to describe, what happens in my head when I think about things," he says. "When I think about my fiancée there is no image, but I am definitely thinking about her. I know today she has her hair up at the back, she's brunette. But I'm not describing an image I am looking at, I'm remembering features about her — that's the strangest thing, and maybe that is a source of some regret."

The response from his mates is very sympathetic: "You're weird." But while Niel is very relaxed about his inability to picture things, it is often a cause of distress for others. One person who took part in a study into aphantasia said he had started to feel "isolated" and "alone" after discovering that other people could see images in their heads. Being unable to reminisce about his mother after her death led to him being "extremely distraught".

The super-visualiser

At the other end of the spectrum is children's book illustrator, Lauren Beard, whose work on the Fairytale Hairdresser series will be familiar to many six-year-olds. Her career relies on the vivid images that leap into her mind's eye when she reads text from her author. When I met her in her box-room studio in Manchester, she was working on a dramatic scene in the next book. The text describes a baby perilously climbing onto a chandelier. "Straightaway I can visualise this grand glass chandelier in some sort of French kind of ballroom, and the little baby just swinging off it and really heavy thick curtains," she says. "I think I have a strong imagination, so I can create the world and then keep adding to it so it gets sort of bigger and bigger in my mind and the characters too, they sort of evolve. I couldn't really imagine what it's like to not imagine, I think it must be a bit of a shame really."

Not many people have mental imagery as vibrant as Lauren or as blank as Niel. They are the two extremes of visualisation. Adam Zeman, a professor of cognitive and behavioural neurology, wants to compare the lives and experiences of people with aphantasia and its polar-opposite hyperphantasia. His team, based at the University of Exeter, coined the term aphantasia this year in a study in the journal Cortex.

Prof Zeman tells the BBC: "People who have contacted us say they are really delighted that this has been recognised and has been given a name, because they have been trying to explain to people for years that there is this oddity that they find hard to convey to others."

How we imagine is clearly very subjective — one person's vivid scene could be another's grainy picture. But Prof Zeman is certain that aphantasia is real. People often report being able to dream in pictures, and there have been reported cases of people losing the ability to think in images after a brain injury. He is adamant that aphantasia is "not a disorder" and says it may affect up to one in 50 people. But he adds: "I think it makes quite an important difference to their experience of life because many of us spend our lives with imagery hovering somewhere in the mind's eye which we inspect from time to time — it's a variability of human experience."
TEXT;
    }

    /**
     * Reading Passage 2 — "Life lessons from villains, crooks and
     * gangsters" (transcribed from the client's question paper;
     * paragraph letters A–H preserved — the heading-matching questions
     * refer to them).
     */
    private function passageTwo(): string
    {
        return <<<'TEXT'
(A) A notorious Mexican drug baron's audacious escape from prison in July doesn't, at first, appear to have much to teach corporate boards. But some in the business world suggest otherwise. Beyond the morally reprehensible side of criminals' work, some business gurus say organised crime syndicates, computer hackers, pirates and others operating outside the law could teach legitimate corporations a thing or two about how to hustle and respond to rapid change.

(B) Far from encouraging illegality, these gurus argue that — in the same way big corporations sometimes emulate start-ups — business leaders could learn from the underworld about flexibility, innovation and the ability to pivot quickly. "There is a nimbleness to criminal organisations that legacy corporations [with large, complex layers of management] don't have," said Marc Goodman, head of the Future Crimes Institute and global cyber-crime advisor. While traditional businesses focus on rules they have to follow, criminals look to circumvent them. "For criminals, the sky is the limit and that creates the opportunity to think much, much bigger."

(C) Joaquin Guzman, the head of the Mexican Sinaloa drug cartel, for instance, slipped out of his prison cell through a tiny hole in his shower that led to a mile-long tunnel fitted with lights and ventilation. Making a break for it required creative thinking, long-term planning and perseverance — essential skills similar to those needed to achieve success in big business.

(D) While Devin Liddell, who heads brand strategy for Seattle-based design consultancy Teague, condemns the violence and other illegal activities, he became curious as to how criminal groups endure. Some cartels stay in business despite multiple efforts by law enforcement on both sides of the US border and millions of dollars from international agencies to shut them down. Liddell genuinely believes there's a lesson in longevity here. One strategy he underlined was how the bad guys respond to change. In order to bypass the border between Mexico and the US, for example, the Sinaloa cartel went to great lengths. It built a vast underground tunnel, hired family members as border agents and even used a catapult to circumvent a high-tech fence.

(E) By contrast, many legitimate businesses fail because they hesitate to adapt quickly to changing market winds. One high-profile example is movie and game rental company Blockbuster, which didn't keep up with the market and lost business to mail order video rentals and streaming technologies. The brand has all but faded from view. Liddell argues the difference between the two groups is that criminal organisations often have improvisation encoded into their daily behaviour, while larger companies think of innovation as a set process. "This is a leadership challenge," said Liddell. "How well companies innovate and organise is a reflection of leadership."

(F) Cash-strapped start-ups also use unorthodox strategies to problem solve and build their businesses up from scratch. This creativity and innovation is often borne out of necessity, such as tight budgets. Both criminals and start-up founders "question authority, act outside the system and see new and clever ways of doing things," said Goodman. "Either they become Elon Musk or El Chapo." And some entrepreneurs aren't even afraid to operate in legal grey areas in their effort to disrupt the marketplace. The co-founders of music streaming service Napster, for example, knowingly broke music copyright rules with their first online file sharing service, but their technology paved the way for legal innovation as regulators caught up.

(G) Goodman and others believe thinking hard about problem solving before worrying about restrictions could prevent established companies falling victim to rivals less constrained by tradition. In their book The Misfit Economy, Alexa Clay and Kyra Maya Phillips examine how individuals can apply that mindset to become more innovative and entrepreneurial within corporate structures. They studied not just violent criminals like Somali pirates, but others who break the rules in order to find creative solutions to their business problems, such as people living in the slums of Mumbai or computer hackers. They picked out five common traits among this group: the ability to hustle, pivot, provoke, hack and copycat.

(H) Clay gives a Saudi entrepreneur named Walid Abdul-Wahab as a prime example. Abdul-Wahab worked with Amish farmers to bring camel milk to American consumers even before US regulators approved it. Through perseverance, he eventually found a network of Amish camel milk farmers and started selling the product via social media. Now his company, Desert Farms, sells to giant mainstream retailers like Whole Foods Market. Those on the fringe don't always have the option of traditional, corporate jobs and that forces them to think more creatively about how to make a living, Clay said. They must develop grit and resilience in order to last outside the cushy confines of cubicle life. "In many cases scarcity is the mother of invention," Clay said.
TEXT;
    }

    /**
     * Reading Passage 3 — "Britain needs strong TV industry"
     * (transcribed from the client's question paper).
     */
    private function passageThree(): string
    {
        return <<<'TEXT'
Comedy writer Armando Iannucci has called for an industry-wide defence of the BBC and British programme-makers. The Thick of It creator made his remarks in the annual MacTaggart Lecture at the Edinburgh TV Festival. "It's more important than ever that we have more strong, popular channels… that act as beacons, drawing audiences to the best content," he said. Speaking earlier, Culture Secretary John Whittingdale rejected suggestions that he wanted to dismantle the BBC.

'Champion supporters'

Iannucci co-wrote I'm Alan Partridge, wrote the movie In the Loop and created and wrote the hit HBO and Sky Atlantic show Veep. He delivered the 40th annual MacTaggart Lecture, which has previously been given by Oscar winner Kevin Spacey, former BBC director general Greg Dyke, Jeremy Paxman and Rupert Murdoch. Iannucci said: "Faced with a global audience, British television needs its champion supporters." He continued his praise for British programming by saying the global success of American TV shows had come about because they were emulating British television. "The best US shows are modelling themselves on what used to make British TV so world-beating," he said. "US prime-time schedules are now littered with those quirky formats from the UK — the Who Do You Think You Are's and the variants on Strictly Come Dancing — as well as the single-camera non-audience sitcom, which we brought into the mainstream first. We have changed international viewing for the better."

With the renewal of the BBC's royal charter approaching, Iannucci also praised the corporation. He said: "If public service broadcasting — one of the best things we've ever done creatively as a country — if it was a car industry, our ministers would be out championing it overseas, trying to win contracts, boasting of the British jobs that would bring."

In July, the government issued a green paper setting out issues that will be explored during negotiations over the future of the BBC, including the broadcaster's size, its funding and governance. Primarily Mr Whittingdale wanted to appoint a panel of five people, but finally he invited two more people to advise on the charter renewal, namely former Channel 4 boss Dawn Airey and journalism professor Stewart Purvis, a former editor-in-chief of ITN.

Iannucci bemoaned the lack of "creatives" involved in the discussions. "When the media, communications and information industries make up nearly 8% of our GDP, larger than the car and oil and gas industries put together, we need to be heard, as those industries are heard. But when I see the panel of experts who've been asked by the culture secretary to take a root and branch look at the BBC, I don't see anyone who is a part of that cast and crew list. I see executives, media owners, industry gurus, all talented people — but not a single person who's made a classic and enduring television show."

'Don't be modest'

Iannucci suggested one way of easing the strain on the license fee was "by pushing ourselves more commercially abroad". "Use the BBC's name, one of the most recognised brands in the world," he said. "And use the reputation of British television across all networks, to capitalise financially overseas. Be more aggressive in selling our shows, through advertising, through proper international subscription channels, freeing up BBC Worldwide to be fully commercial, whatever it takes. Frankly, don't be icky and modest about making money, let's monetise the bezeesus Mary and Joseph out of our programmes abroad so that money can come back, take some pressure off the licence fee at home and be invested in even more ambitious quality shows — that can only add to our value."

Mr Whittingdale, who was interviewed by ITV News' Alastair Stewart at the festival, said he wanted an open debate about whether the corporation should do everything it has done in the past. He said he had a slight sense that people who rushed to defend the BBC were "trying to have an argument that's never been started". "Whatever my view is, I don't determine what programmes the BBC should show," he added. "That's the job of the BBC." Mr Whittingdale said any speculation that the Conservative Party had always wanted to change the BBC due to issues such as its editorial line was "absolute nonsense".
TEXT;
    }
}

/*
|--------------------------------------------------------------------------
| NOT YET SEEDED — typed completion questions (awaiting a text_input qtype)
|--------------------------------------------------------------------------
| Texts from the question paper; answers from the client's official key.
| Correct spelling is required (per the answer sheet).
|
| Passage 1 — Questions 9–13 (NO MORE THAN TWO WORDS):
|   9.  "Only a small fraction of people have imagination as ___ as Lauren does." → Vibrant
|   10. "Hyperphantasia is ___ to aphantasia." → Polar-opposite
|   11. "There are a lot of subjectivity in comparing people's imagination — somebody's vivid scene could be another person's ___." → Grainy picture
|   12. "Prof Zeman is ___ that aphantasia is not an illness." → Adamant
|   13. "Many people spend their lives with ___ somewhere in the mind's eye." → Imagery hovering
|
| Passage 2 — Questions 22–25 (ONLY ONE WORD):
|   22. "To escape from a prison, Joaquin Guzman had to use such traits as creative thinking, long-term planning and ___." → Perseverance
|   23. "The Sinaloa cartel built a grand underground tunnel and even used ___ to avoid the fence." → Catapult
|   24. "The main difference between two groups is that criminals, unlike large corporations, often have ___ encoded into their daily life." → Improvisation
|   25. "Due to being persuasive, Walid Abdul-Wahab found a ___ of Amish camel milk farmers." → Network
|
| Passage 3 — Questions 36–40 (summary completion, NO MORE THAN TWO WORDS):
|   36. "Iannucci recommended increasing BBC's profit by pushing ourselves more ___." → commercially abroad
|   37. "He suggests being more aggressive in selling British shows, through advertising and proper international ___." → subscription channels
|   38. "Also, he invokes producers to stop being ___ and modest about making money" → icky
|   39. "…and invest into even ___ quality shows." → more ambitious
|   40. "However, Mr Whittingdale denied any ___ that the Conservative Party had always wanted to change the BBC because of its editorial line." → speculation
*/
