use std::process::Command;

#[test]
fn test_application_runs() {
    let output = Command::new("cargo")
        .arg("run")
        .arg("--quiet")
        .output()
        .expect("Failed to execute command");

    assert!(
        output.status.success(),
        "Application didn't run successfully"
    );
}

#[test]
fn test_correct_output() {
    let output = Command::new("cargo")
        .arg("run")
        .arg("--quiet")
        .output()
        .expect("Failed to execute command");

    let stdout = String::from_utf8(output.stdout).expect("Output was not valid UTF-8");
    assert_eq!(stdout.trim(), "Hello, world!");
}
