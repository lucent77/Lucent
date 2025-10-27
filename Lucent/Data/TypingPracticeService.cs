using System;
using System.Collections.Generic;
using System.Linq;

namespace Lucent.Data
{
    public class TypingPracticeService
    {
        private static readonly string[] KoreanTexts = new[]
        {
            "안녕하세요. 한글 타자 연습을 시작합니다.",
            "빠른 갈색 여우가 게으른 개를 뛰어넘습니다.",
            "연습은 완벽을 만듭니다.",
            "꾸준한 노력이 성공의 열쇠입니다.",
            "한글은 세계에서 가장 과학적인 문자입니다.",
            "오늘도 좋은 하루 되세요.",
            "타자 연습을 통해 속도를 향상시킬 수 있습니다.",
            "컴퓨터 프로그래밍은 논리적 사고를 요구합니다.",
            "꿈을 이루기 위해서는 끊임없는 도전이 필요합니다.",
            "세상을 바꾸는 것은 작은 실천에서 시작됩니다."
        };

        private static readonly Random Random = new Random();

        public string GetRandomText()
        {
            return KoreanTexts[Random.Next(KoreanTexts.Length)];
        }

        public TypingResult CalculateResult(string targetText, string typedText, TimeSpan elapsedTime)
        {
            var result = new TypingResult
            {
                TargetText = targetText,
                TypedText = typedText,
                ElapsedTime = elapsedTime
            };

            if (string.IsNullOrEmpty(typedText))
            {
                return result;
            }

            // 정확도 계산
            int correctChars = 0;
            int minLength = Math.Min(targetText.Length, typedText.Length);

            for (int i = 0; i < minLength; i++)
            {
                if (targetText[i] == typedText[i])
                {
                    correctChars++;
                }
            }

            result.Accuracy = (double)correctChars / targetText.Length * 100;
            result.CorrectCharacters = correctChars;
            result.TotalCharacters = targetText.Length;

            // 타자 속도 계산 (분당 타수)
            if (elapsedTime.TotalMinutes > 0)
            {
                result.CharsPerMinute = typedText.Length / elapsedTime.TotalMinutes;
            }

            return result;
        }
    }

    public class TypingResult
    {
        public string TargetText { get; set; }
        public string TypedText { get; set; }
        public TimeSpan ElapsedTime { get; set; }
        public double Accuracy { get; set; }
        public int CorrectCharacters { get; set; }
        public int TotalCharacters { get; set; }
        public double CharsPerMinute { get; set; }
    }
}
