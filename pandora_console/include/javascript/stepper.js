function Stepper(container, steps) {
  if (container instanceof jQuery) {
    this.container = container[0];
  } else {
    this.container = container;
  }

  this.steps = [];

  for (var i = 1; i <= steps.length; i++) {
    var stepContainer = document.createElement("div");
    stepContainer.className = "step-container";
    var step = document.createElement("span");
    step.className = "step";
    step.textContent = i;
    var text = document.createElement("div");
    text.className = "step-text";
    text.textContent = steps[i - 1];
    stepContainer.appendChild(step);
    stepContainer.appendChild(text);
    this.steps.push(stepContainer);
  }
}

Stepper.prototype.render = function() {
  var separator = document.createElement("div");
  separator.className = "step-separator";
  var stepsContainer = document.createElement("div");
  stepsContainer.className = "steps";

  for (var i = 0; i < this.steps.length; i++) {
    if (i > 0) {
      stepsContainer.appendChild(separator.cloneNode());
    }
    stepsContainer.appendChild(this.steps[i]);
  }

  this.container.innerHTML = "";
  this.container.appendChild(stepsContainer);
};

Stepper.prototype.selectStep = function(step) {
  for (var i = 0; i < this.steps.length; i++) {
    if (i < step - 1) {
      this.steps[i].querySelector(".step").classList.add("visited");
      this.steps[i].querySelector(".step-text").classList.remove("active");
      var separators = this.container.querySelectorAll(".step-separator");
      if (separators[i]) {
        separators[i].classList.add("visited");
      }
    } else if (i === step - 1) {
      this.steps[i].querySelector(".step").classList.add("active");
      this.steps[i].querySelector(".step-text").classList.add("active");
      this.steps[i].querySelector(".step").classList.remove("visited");
    } else {
      this.steps[i]
        .querySelector(".step")
        .classList.remove("visited", "active");

      this.steps[i].querySelector(".step-text").classList.remove("active");

      var separators = this.container.querySelectorAll(".step-separator");

      if (separators[i - 1]) {
        separators[i - 1].classList.remove("visited");
      }
    }
  }
};
