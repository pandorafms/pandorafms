import {
  parseIntOr,
  stringIsEmpty,
  notEmptyStringOr,
  leftPad,
  prefixedCssRules,
  decodeBase64,
  humanDate,
  humanTime,
  replaceMacros,
  itemMetaDecoder
} from ".";

describe("function parseIntOr", () => {
  it("should retrieve valid int or a default value", () => {
    expect(parseIntOr("Foo", null)).toBe(null);
    expect(parseIntOr("1a", null)).toBe(1);
    expect(parseIntOr("a1", null)).toBe(null);
    expect(parseIntOr("1", null)).toBe(1);
    expect(parseIntOr(false, null)).toBe(null);
    expect(parseIntOr(true, null)).toBe(null);
    expect(parseIntOr(1, null)).toBe(1);
  });
});

describe("function stringIsEmpty", () => {
  it("should check properly if a string is empry or not", () => {
    expect(stringIsEmpty()).toBe(true);
    expect(stringIsEmpty("")).toBe(true);
    expect(stringIsEmpty("foo")).toBe(false);
    expect(stringIsEmpty("bar")).toBe(false);
  });
});

describe("function notEmptyStringOr", () => {
  it("should retrieve not empty string or a default value", () => {
    expect(notEmptyStringOr("", null)).toBe(null);
    expect(notEmptyStringOr("Foo", null)).toBe("Foo");
    expect(notEmptyStringOr(1, 1)).toBe(1);
    expect(notEmptyStringOr(1, 0)).toBe(0);
    expect(notEmptyStringOr("", 0)).toBe(0);
    expect(notEmptyStringOr("Foo", "Bar")).toBe("Foo");
    expect(notEmptyStringOr(0, "Bar")).toBe("Bar");
  });
});

describe("function leftPad", () => {
  it("should pad properly", () => {
    expect(leftPad(1, 2, 0)).toBe("01");
    expect(leftPad(1, 4, 0)).toBe("0001");
    expect(leftPad(1, 4, "0")).toBe("0001");
    expect(leftPad("1", 4, "0")).toBe("0001");
    expect(leftPad(10, 4, 0)).toBe("0010");
    expect(leftPad("bar", 6, "foo")).toBe("foobar");
    expect(leftPad("bar", 11, "foo")).toBe("foofoofobar");
    expect(leftPad("bar", 4, "foo")).toBe("fbar");
    expect(leftPad("bar", 2, "foo")).toBe("ar");
    expect(leftPad("bar", 3, "foo")).toBe("bar");
  });
});

describe("function prefixedCssRules", () => {
  it("should add the prefixes to the rules", () => {
    const rules = prefixedCssRules("transform", "rotate(0)");
    expect(rules).toContainEqual("transform: rotate(0);");
    expect(rules).toContainEqual("-webkit-transform: rotate(0);");
    expect(rules).toContainEqual("-moz-transform: rotate(0);");
    expect(rules).toContainEqual("-ms-transform: rotate(0);");
    expect(rules).toContainEqual("-o-transform: rotate(0);");
    expect(rules).toHaveLength(5);
  });
});

describe("function decodeBase64", () => {
  it("should decode the base64 without errors", () => {
    expect(decodeBase64("SGkgSSdtIGRlY29kZWQ=")).toBe("Hi I'm decoded");
    expect(decodeBase64("Rk9PQkFSQkFa")).toBe("FOOBARBAZ");
    expect(decodeBase64("eyJpZCI6MSwibmFtZSI6ImZvbyJ9")).toBe(
      '{"id":1,"name":"foo"}'
    );
    expect(
      decodeBase64("PGRpdj5Cb3ggPHA+UGFyYWdyYXBoPC9wPjxociAvPjwvZGl2Pg==")
    ).toBe("<div>Box <p>Paragraph</p><hr /></div>");
  });
});

describe("humanDate function", () => {
  it("should return the date with padded 0's", () => {
    const expected = "01/02/0123";
    const date = new Date(`02/01/0123 12:00:00`);
    const digitalDate = humanDate(date);
    expect(digitalDate).toBe(expected);
  });
});

describe("humanTime function", () => {
  it("should return the time with padded 0's when hours/minutes/seconds are less than 10", () => {
    const expected = "01:02:03";
    const date = new Date(`01/01/1970 ${expected}`);
    const digitalTime = humanTime(date);
    expect(digitalTime).toBe(expected);
  });
});

describe("replaceMacros function", () => {
  const macros = [
    { macro: "_foo_", value: "foo" },
    { macro: "_bar_", value: "bar" },
    { macro: "_baz_", value: "baz" }
  ];

  it("should not replace anything if it doesn't find any macro", () => {
    const text = "Lorem Ipsum";
    expect(replaceMacros(macros, text)).toBe(text);
  });

  it("should replace the macros", () => {
    const text = "Lorem _foo_ Ipsum _baz_";
    expect(replaceMacros(macros, text)).toBe("Lorem foo Ipsum baz");
  });
});

describe("itemMetaDecoder function", () => {
  it("should extract a default meta object", () => {
    expect(
      itemMetaDecoder({
        receivedAt: 1
      })
    ).toEqual({
      receivedAt: new Date(1000),
      error: null,
      isFromCache: false,
      isFetching: false,
      isUpdating: false,
      editMode: false
    });
  });

  it("should extract a valid meta object", () => {
    expect(
      itemMetaDecoder({
        receivedAt: new Date(1000),
        error: new Error("foo"),
        editMode: 1
      })
    ).toEqual({
      receivedAt: new Date(1000),
      error: new Error("foo"),
      isFromCache: false,
      isFetching: false,
      isUpdating: false,
      editMode: true
    });
  });

  it("should fail when a invalid structure is used", () => {
    expect(() => itemMetaDecoder({})).toThrowError(TypeError);
    expect(() =>
      itemMetaDecoder({
        receivedAt: "foo"
      })
    ).toThrowError(TypeError);
  });
});
