> **Note:** Please fill out all required sections and remove irrelevant ones.

### 📋 Kind of PR 

- [ ] Fixes a bug
- [ ] Updates for a new Moodle version
- [ ] Adds a new feature of functionality
- [ ] Improves or enhances existing features
- [ ] Refactoring: restructures code for better performance or maintainability
- [ ] Testing: add missing or improve existing tests
- [ ] Miscellaneous: code cleaning (without functional changes), documentation, configuration, ...
---

### 🧩 Description

Please describe the purpose of this PR in a few sentences.

- What feature or bug does it address?
- Why is this change or addition necessary?
- What is the expected behavior after the change?

---

### ✅ Checklist

Please confirm the following (check all that apply):

- [ ] I have `phpunit` and/or `behat` tests that cover my changes or additions.
- [ ] Code passes the code checker without errors and warnings.
- [ ] Code passes the moodle-ci/cd pipeline on all supported Moodle versions or the ones the plugin supports.
- [ ] Code does not have `var_dump()` or `var_export` or any other debugging statements that should not appear on the productive branch.
- [ ] Code only uses language strings instead of hard-coded strings.
- [ ] If there are changes in the database: I updated/created the necessary upgrade steps in `db/upgrade.php` and updated the `version.php`.
- [ ] If there are changes in javascript: I build new `.min` files with the `grunt amd` command.
- [ ] If it is a Moodle update PR: I read the release notes, updated the `version.php` and added the new moodle version to the workflow file. I ran all tests thoroughly checking for errors.

---

### 🧱 Related Issue

- Related to #[IssueNumber]

---

### 🗒️ Additional Notes

Any other relevant information.
