## 1. Repository layer

- [x] 1.1 Make `$channel` parameter required in `NavigationRepositoryInterface::findOneEnabledByCode`
- [x] 1.2 Update `NavigationRepository::findOneEnabledByCode` — remove `IS EMPTY` fallback and `null === $channel` branch, require `:channel MEMBER OF o.channels`
- [x] 1.3 Update existing tests for `NavigationRepository` if any, or add tests covering: matching channel returns navigation, non-matching channel returns null, empty channels returns null

## 2. Graph builder

- [x] 2.1 Simplify `GraphBuilder::build` line 53 — change `!$descendant->getChannels()->isEmpty() && !$descendant->hasChannel($channel)` to `!$descendant->hasChannel($channel)`
- [x] 2.2 Update `GraphBuilderTest` to cover: item with matching channel included, item with non-matching channel excluded, item with no channels excluded

## 3. Build controller

- [x] 3.1 Simplify `BuildController::isItemVisibleOnChannel` — change `$item->getChannels()->isEmpty() || $item->hasChannel($channel)` to `$item->hasChannel($channel)`
- [x] 3.2 Update the docblock on `isItemVisibleOnChannel` to remove the "Empty channels means visible on all channels" comment

## 4. Forms

- [x] 4.1 Set `required: true` on the channels field in `NavigationType`
- [x] 4.2 Set `required: true` on the channels field in `ItemType`
- [x] 4.3 Update `NavigationTypeTest` and `ItemTypeTest` to verify channels is required

## 5. Verification

- [x] 5.1 Run `composer analyse` and fix any PHPStan errors from the signature change
- [x] 5.2 Run `composer phpunit` and ensure all tests pass
- [x] 5.3 Run `composer check-style` and fix any style issues
