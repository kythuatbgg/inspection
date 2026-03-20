# Mobile Webview Review Checklist

Use this checklist before declaring any responsive mobile screen complete.

## Structure
- [ ] Did I keep desktop and mobile shared only where it still helps usability?
- [ ] Did I split markup where desktop structure harms mobile clarity?
- [ ] Is the mobile screen understandable in a strict top-to-bottom flow?

## Touch and Reach
- [ ] Are all key actions at least 44px tall?
- [ ] Are primary actions easier to reach than secondary ones?
- [ ] Are destructive actions visually separated enough to prevent mistakes?

## Visual Hierarchy
- [ ] Is the title clear immediately?
- [ ] Does each card have one strong primary identifier?
- [ ] Are metadata groups visually chunked instead of scattered?

## Search and Filters
- [ ] Is search full-width or otherwise easy to use on narrow screens?
- [ ] Are filters reduced to only the most important mobile controls?
- [ ] Does the search area look intentional rather than copied from desktop?

## Actions
- [ ] Are action rows limited and readable?
- [ ] If there is a primary create action, is a FAB or bottom-zone CTA more appropriate?
- [ ] Are icon-only controls used only when the meaning is unmistakable?

## Pagination
- [ ] Is mobile pagination separate from desktop if needed?
- [ ] Can users move between pages one-handed?
- [ ] Does pagination avoid crowding and tiny targets?

## Fixed and Floating Elements
- [ ] Do fixed bars avoid covering content?
- [ ] Does the screen have enough bottom spacing for FABs or floating controls?
- [ ] If there is bottom pagination, does the floating primary action stay fully clear of `next` / `previous` buttons at narrow widths?
- [ ] Is there any accidental overlap at narrow widths?

## Empty and Error States
- [ ] Does empty state provide context, not just absence?
- [ ] Does the user know what to do next?
- [ ] Are loading and error states still readable on mobile?

## Final Validation
- [ ] No horizontal scroll at ~375px width
- [ ] No cramped toolbar feeling
- [ ] No tiny tap targets
- [ ] No visually noisy card layout
- [ ] The mobile version feels intentionally designed, not compressed from desktop

## Scores, Badges, and Status Indicators
- [ ] Are score/result values visible at the list level without opening details?
- [ ] Do critical error counts use conditional styling (red when >0, neutral when 0)?
- [ ] Are status badges (pass/fail, approved/rejected) consistently styled across desktop and mobile?
- [ ] Do badges use both color AND text (not color alone) for accessibility?

## Icons
- [ ] Are all icons from a single library (e.g., `lucide-vue-next`) — no inline SVGs?
- [ ] Are icon sizes consistent within context (e.g., `w-4 h-4` for inline, `w-5 h-5` for buttons)?
- [ ] Do loading spinners have `animate-spin` applied?
- [ ] Were Vue directives preserved on icon components after any automated replacement?

## Modals and Detail Views
- [ ] Do modals use full-screen takeover on mobile, centered overlay on desktop?
- [ ] Does modal body use `overflow-y-auto overscroll-contain`?
- [ ] Is the close button large enough and positioned for thumb reach?
- [ ] Do action-heavy modals have a sticky footer for primary actions?

## Integration and API
- [ ] Does the auth store unwrap `response.data.data` for envelope-wrapped APIs?
- [ ] Is the API base URL proxy-safe for tunnel/LAN dev environments?
- [ ] Are all API services consistent in how they extract response payloads?

## Location and Maps
- [ ] Do items with coordinates show a tappable map link (not just text)?
- [ ] Does the map link have expanded touch area (`p-1 -m-1`)?
- [ ] Is there a graceful fallback (`—`) when coordinates are missing?
