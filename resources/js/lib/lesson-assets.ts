// Keep older lesson content and saved drafts usable on an isolated LAN.
// Transform previews only: students' saved source remains intact.
export function localLessonImages(markup: string): string {
    return markup.replace(
        /https:\/\/placehold\.co\/\d+x\d+(?:\?[^\s"'<>]*)?/g,
        '/images/lesson-placeholder.svg',
    );
}
