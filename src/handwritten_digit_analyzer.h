#ifndef HANDWRITTEN_DIGIT_ANALYZER_H
#define HANDWRITTEN_DIGIT_ANALYZER_H

#include "neural_network.h"
#include <fstream>

namespace digit_recognizer
{

class handwritten_digits_analyzer
{
public:
	handwritten_digits_analyzer(vector<size_t> hiddenLayer, double learningRate);
	// label means actual number (0-9)
	vector<double> trainWithBinaryFile(size_t numIter, std::string trainImage, std::string trainLabel,
									   std::string testImage, std::string testLabel);
	vector<double> trainWithTextFile(size_t numIter, std::string trainFileName, std::string testFileName);

private:
	vector<std::pair<vector<double>, vector<double>>>
	readBinaryFile(std::string imageFileName, std::string labelFileName) const;
	vector<std::pair<vector<double>, vector<double>>> readFile(std::string fileName, size_t count) const;
	unsigned char readUnsignedByte(std::ifstream &) const;
	// some data is stored in big endian. Convert this to little endian and return the value.
	uint32_t readUnsignedInt(std::ifstream &) const;
	uint32_t convertFromBigEndian(char[4]) const;
	void showBytes(std::ifstream &, size_t size) const;

private:
	neural_network neuralNetwork_;
};

} // namespace digit_recognizer
#endif